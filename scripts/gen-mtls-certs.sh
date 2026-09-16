#!/usr/bin/env bash
set -eo pipefail

echo "🔐 Generating mTLS certificates for ai-egress-proxy (§8.1.4)..."

CERTS_DIR="docker/certs/proxy"
mkdir -p "${CERTS_DIR}"

# 1. Generate Root CA
if [ ! -f "${CERTS_DIR}/ca.key" ]; then
    echo "Creating Root CA..."
    openssl req -x509 -newkey rsa:4096 -days 3650 -nodes         -keyout "${CERTS_DIR}/ca.key"         -out "${CERTS_DIR}/ca.crt"         -subj "/C=IR/ST=Tehran/L=Tehran/O=Pishkhan/OU=Security/CN=Pishkhan-mTLS-CA"
fi

# 2. Generate Server Certificate
if [ ! -f "${CERTS_DIR}/server.key" ]; then
    echo "Creating Server Cert..."
    openssl req -newkey rsa:2048 -nodes         -keyout "${CERTS_DIR}/server.key"         -out "${CERTS_DIR}/server.csr"         -subj "/C=IR/ST=Tehran/L=Tehran/O=Pishkhan/OU=Egress/CN=ai-proxy.internal"

    openssl x509 -req -in "${CERTS_DIR}/server.csr"         -CA "${CERTS_DIR}/ca.crt"         -CAkey "${CERTS_DIR}/ca.key"         -CAcreateserial         -out "${CERTS_DIR}/server.crt"         -days 365
    rm -f "${CERTS_DIR}/server.csr"
fi

# 3. Generate Client Certificate for Backend Servers
if [ ! -f "${CERTS_DIR}/client.key" ]; then
    echo "Creating Client Cert..."
    openssl req -newkey rsa:2048 -nodes         -keyout "${CERTS_DIR}/client.key"         -out "${CERTS_DIR}/client.csr"         -subj "/C=IR/ST=Tehran/L=Tehran/O=Pishkhan/OU=Backend/CN=api-worker.pishkhan.ir"

    openssl x509 -req -in "${CERTS_DIR}/client.csr"         -CA "${CERTS_DIR}/ca.crt"         -CAkey "${CERTS_DIR}/ca.key"         -CAcreateserial         -out "${CERTS_DIR}/client.crt"         -days 365
    rm -f "${CERTS_DIR}/client.csr"
fi

echo "✅ mTLS certificates successfully created in ${CERTS_DIR}:"
ls -la "${CERTS_DIR}"
