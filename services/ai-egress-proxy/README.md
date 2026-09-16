# AI Egress Proxy (Architecture §8.1.4, HC-7)

## Purpose
The **only component located outside Iran**. It acts as an isolated, stateless, diskless reverse proxy forwarding whitelisted AI requests to `openrouter.ai`.

## Security Invariants (§8.1.4):
1. **Mandatory mTLS**: Requires and validates trusted client certificates from authorized Iran server IPs.
2. **Whitelist-only paths**: Strictly `/v1/chat/completions` and `/v1/audio/transcriptions`. All other routes return `404`.
3. **Stateless & Diskless**: Container runs as `scratch` scratchpad with 0 disk writes and 0 persistence.
4. **Metadata-only logging**: Timestamps, models, token counts, latency, client CN, and status code. **Never logs prompt content or response content.**
5. **Key isolation**: `OPENROUTER_API_KEY` is stored strictly on this proxy environment, never on servers in Iran.
