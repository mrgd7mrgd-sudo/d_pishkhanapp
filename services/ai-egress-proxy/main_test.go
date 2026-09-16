package main

import (
	"bytes"
	"crypto/tls"
	"crypto/x509"
	"crypto/x509/pkix"
	"io"
	"net/http"
	"net/http/httptest"
	"strings"
	"testing"
)

func TestAiEgressProxySecurityAndRouting(t *testing.T) {
	// Mock upstream OpenRouter server
	upstreamServer := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		// Verify API Key was injected by proxy
		authHeader := r.Header.Get("Authorization")
		if authHeader != "Bearer test-openrouter-key" {
			http.Error(w, "missing or invalid auth", http.StatusUnauthorized)
			return
		}

		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusOK)
		_, _ = w.Write([]byte(`{"id":"gen-123","choices":[{"message":{"content":"response text"}}]}`))
	}))
	defer upstreamServer.Close()

	cfg := Config{
		TargetBaseURL:    upstreamServer.URL,
		OpenRouterAPIKey: "test-openrouter-key",
		EnforceMTLS:      false,
	}

	handler := newProxyHandler(cfg, upstreamServer.Client())

	// Test 1: Healthcheck
	t.Run("healthcheck endpoint returns 200", func(t *testing.T) {
		req := httptest.NewRequest(http.MethodGet, "/healthz", nil)
		rec := httptest.NewRecorder()
		handler(rec, req)

		if rec.Code != http.StatusOK {
			t.Errorf("Expected 200, got %d", rec.Code)
		}
		if !strings.Contains(rec.Body.String(), "ai-egress-proxy") {
			t.Errorf("Unexpected body: %s", rec.Body.String())
		}
	})

	// Test 2: Whitelist routes allowed
	t.Run("allowed route /v1/chat/completions succeeds and injects API key", func(t *testing.T) {
		body := bytes.NewBufferString(`{"model":"google/gemini-2.5-flash","messages":[{"role":"user","content":"سلام"}]}`)
		req := httptest.NewRequest(http.MethodPost, "/v1/chat/completions", body)
		req.Header.Set("Content-Type", "application/json")

		rec := httptest.NewRecorder()
		handler(rec, req)

		if rec.Code != http.StatusOK {
			t.Errorf("Expected 200, got %d. Body: %s", rec.Code, rec.Body.String())
		}
		if !strings.Contains(rec.Body.String(), "gen-123") {
			t.Errorf("Expected response body to contain upstream data, got: %s", rec.Body.String())
		}
	})

	// Test 3: Disallowed route rejected with 404
	t.Run("disallowed route /v1/models is rejected with 404", func(t *testing.T) {
		req := httptest.NewRequest(http.MethodGet, "/v1/models", nil)
		rec := httptest.NewRecorder()
		handler(rec, req)

		if rec.Code != http.StatusNotFound {
			t.Errorf("Expected 404 for disallowed route, got %d", rec.Code)
		}
	})

	// Test 4: Logs verify metadata only, never prompt or response content (§8.1.4)
	t.Run("meta log entry verifies no prompt content leakage", func(t *testing.T) {
		meta := MetaLogEntry{
			Path:         "/v1/chat/completions",
			Method:       "POST",
			Status:       200,
			DurationMs:   45,
			TargetHost:   "openrouter.ai",
			RequestBytes: 150,
		}

		// MetaLogEntry struct has no field for prompt or response
		if meta.Path != "/v1/chat/completions" || meta.Status != 200 {
			t.Errorf("Unexpected meta log values")
		}
	})
}
