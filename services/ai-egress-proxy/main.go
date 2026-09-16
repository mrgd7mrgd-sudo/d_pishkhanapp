package main

import (
	"crypto/tls"
	"crypto/x509"
	"encoding/json"
	"fmt"
	"io"
	"log"
	"net/http"
	"net/url"
	"os"
	"strings"
	"time"
)

// Config represents proxy configuration
type Config struct {
	Port              string
	TargetBaseURL     string
	OpenRouterAPIKey  string
	ServerCertPath    string
	ServerKeyPath     string
	ClientCACertPath  string
	EnforceMTLS       bool
}

// MetaLogEntry logs only metadata, never prompt or response content (§8.1.4)
type MetaLogEntry struct {
	Timestamp    string `json:"timestamp"`
	Path         string `json:"path"`
	Method       string `json:"method"`
	Status       int    `json:"status"`
	DurationMs   int64  `json:"duration_ms"`
	ClientCN     string `json:"client_cn,omitempty"`
	TargetHost   string `json:"target_host"`
	RequestBytes int64  `json:"request_bytes"`
}

func loadConfig() Config {
	port := os.Getenv("PORT")
	if port == "" {
		port = "8443"
	}

	target := os.Getenv("TARGET_BASE_URL")
	if target == "" {
		target = "https://openrouter.ai/api"
	}

	apiKey := os.Getenv("OPENROUTER_API_KEY")

	enforceMTLS := os.Getenv("ENFORCE_MTLS") != "false"

	return Config{
		Port:             port,
		TargetBaseURL:    target,
		OpenRouterAPIKey: apiKey,
		ServerCertPath:   os.Getenv("SERVER_CERT_PATH"),
		ServerKeyPath:    os.Getenv("SERVER_KEY_PATH"),
		ClientCACertPath: os.Getenv("CLIENT_CA_CERT_PATH"),
		EnforceMTLS:      enforceMTLS,
	}
}

// AllowedRoutes defines strict whitelist of proxied paths (§8.1.4)
var allowedRoutes = map[string]bool{
	"/v1/chat/completions":     true,
	"/v1/audio/transcriptions": true,
	"/healthz":                 true,
}

func newProxyHandler(cfg Config, client *http.Client) http.HandlerFunc {
	targetURL, err := url.Parse(cfg.TargetBaseURL)
	if err != nil {
		log.Fatalf("Invalid TARGET_BASE_URL: %v", err)
	}

	return func(w http.ResponseWriter, r *http.Request) {
		start := time.Now()

		// 1. Healthcheck endpoint
		if r.URL.Path == "/healthz" {
			w.Header().Set("Content-Type", "application/json")
			w.WriteHeader(http.StatusOK)
			_, _ = w.Write([]byte(`{"status":"ok","component":"ai-egress-proxy"}`))
			return
		}

		// 2. Strict route whitelist verification
		if !allowedRoutes[r.URL.Path] {
			http.Error(w, `{"error":"route_not_allowed","code":404}`, http.StatusNotFound)
			return
		}

		// 3. Optional mTLS inspection from TLS connection state
		clientCN := ""
		if r.TLS != nil && len(r.TLS.PeerCertificates) > 0 {
			clientCN = r.TLS.PeerCertificates[0].Subject.CommonName
		}

		// 4. Construct upstream request to OpenRouter
		upstreamURL := fmt.Sprintf("%s%s", strings.TrimRight(targetURL.String(), "/"), r.URL.Path)
		if r.URL.RawQuery != "" {
			upstreamURL += "?" + r.URL.RawQuery
		}

		bodyBytes, err := io.ReadAll(r.Body)
		if err != nil {
			http.Error(w, `{"error":"failed_to_read_body"}`, http.StatusBadRequest)
			return
		}
		_ = r.Body.Close()

		reqSize := int64(len(bodyBytes))

		upstreamReq, err := http.NewRequestWithContext(r.Context(), r.Method, upstreamURL, strings.NewReader(string(bodyBytes)))
		if err != nil {
			http.Error(w, `{"error":"failed_to_create_upstream_request"}`, http.StatusInternalServerError)
			return
		}

		// Copy headers except Host
		for k, vv := range r.Header {
			for _, v := range vv {
				upstreamReq.Header.Add(k, v)
			}
		}

		// Inject OpenRouter API key (§8.1.4: stored ONLY on proxy)
		if cfg.OpenRouterAPIKey != "" {
			upstreamReq.Header.Set("Authorization", "Bearer "+cfg.OpenRouterAPIKey)
		}
		upstreamReq.Header.Set("HTTP-Referer", "https://pishkhan.gov.ir")
		upstreamReq.Header.Set("X-Title", "Pishkhan Intelligent Citizen Service")

		// Execute upstream request
		resp, err := client.Do(upstreamReq)
		if err != nil {
			http.Error(w, fmt.Sprintf(`{"error":"upstream_unavailable","detail":"%s"}`, err.Error()), http.StatusBadGateway)
			return
		}
		defer func() { _ = resp.Body.Close() }()

		// Stream response back to caller
		for k, vv := range resp.Header {
			for _, v := range vv {
				w.Header().Add(k, v)
			}
		}
		w.WriteHeader(resp.StatusCode)
		_, _ = io.Copy(w, resp.Body)

		// 5. Strictly structured metadata-only log (§8.1.4: NEVER PROMPT OR RESPONSE)
		duration := time.Since(start).Milliseconds()
		metaLog := MetaLogEntry{
			Timestamp:    time.Now().UTC().Format(time.RFC3339),
			Path:         r.URL.Path,
			Method:       r.Method,
			Status:       resp.StatusCode,
			DurationMs:   duration,
			ClientCN:     clientCN,
			TargetHost:   targetURL.Host,
			RequestBytes: reqSize,
		}

		logBytes, _ := json.Marshal(metaLog)
		log.Println(string(logBytes))
	}
}

func main() {
	cfg := loadConfig()
	log.Printf("Starting ai-egress-proxy on port :%s (mTLS enforced: %v)", cfg.Port, cfg.EnforceMTLS)

	client := &http.Client{
		Timeout: 60 * time.Second,
	}

	handler := newProxyHandler(cfg, client)
	server := &http.Server{
		Addr:         ":" + cfg.Port,
		Handler:      handler,
		ReadTimeout:  30 * time.Second,
		WriteTimeout: 60 * time.Second,
	}

	if cfg.EnforceMTLS && cfg.ServerCertPath != "" && cfg.ServerKeyPath != "" {
		tlsConfig := &tls.Config{
			MinVersion: tls.VersionTLS13,
		}

		if cfg.ClientCACertPath != "" {
			caCert, err := os.ReadFile(cfg.ClientCACertPath)
			if err != nil {
				log.Fatalf("Failed to read client CA cert: %v", err)
			}
			caCertPool := x509.NewCertPool()
			caCertPool.AppendCertsFromPEM(caCert)

			tlsConfig.ClientCAs = caCertPool
			tlsConfig.ClientAuth = tls.RequireAndVerifyClientCert
		}

		server.TLSConfig = tlsConfig
		log.Fatal(server.ListenAndServeTLS(cfg.ServerCertPath, cfg.ServerKeyPath))
	} else {
		log.Fatal(server.ListenAndServe())
	}
}
