FROM golang:1.23-alpine AS builder

WORKDIR /app
COPY go.mod ./
COPY main.go ./

RUN CGO_ENABLED=0 GOOS=linux go build -ldflags="-s -w" -o /ai-egress-proxy main.go

FROM scratch

WORKDIR /
COPY --from=builder /etc/ssl/certs/ca-certificates.crt /etc/ssl/certs/
COPY --from=builder /ai-egress-proxy /ai-egress-proxy

USER 65534:65534
EXPOSE 8443

ENTRYPOINT ["/ai-egress-proxy"]
