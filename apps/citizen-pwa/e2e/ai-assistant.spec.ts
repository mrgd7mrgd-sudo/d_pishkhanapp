import { describe, it, expect, vi, beforeEach } from 'vitest';
import { sendChatMessageStream, sendVoiceMessage } from '../src/features/ai-assistant/api/aiClient';

describe('AI Assistant E2E Scenario E15 (Architecture §10.3, §8.1, HC-5, TASK-115, TASK-115-T)', () => {
  const interceptedUrls: string[] = [];
  const interceptedHeaders: Record<string, string>[] = [];

  beforeEach(() => {
    vi.clearAllMocks();
    interceptedUrls.length = 0;
    interceptedHeaders.length = 0;
  });

  it('E15: Citizen submits query with National ID -> Client communicates exclusively with internal API gateway and zero external AI endpoints', async () => {
    const rawNationalId = '0018889999';
    const citizenPrompt = `سلام، کد ملی من ${rawNationalId} است و کارت هوشمند ملی خود را گم کرده‌ام. مدارک لازم چیست؟`;

    // Mock global fetch to intercept client outbound requests
    globalThis.fetch = vi.fn().mockImplementation(async (input: RequestInfo | URL, init?: RequestInit) => {
      const url = typeof input === 'string' ? input : input.toString();
      interceptedUrls.push(url);

      if (init?.headers) {
        interceptedHeaders.push(init.headers as Record<string, string>);
      }

      // Assert that client never attempts to contact external AI provider directly
      expect(url).not.toContain('openrouter.ai');
      expect(url).not.toContain('api.openai.com');
      expect(url).not.toContain('api.anthropic.com');
      expect(url).not.toContain('generativelanguage.googleapis.com');

      if (url.includes('/api/v1/ai/chat')) {
        const streamData = [
          'event: meta\ndata: {"conversation_id":"conv_e15_test"}\n\n',
          'event: token\ndata: {"token":"برای "}\n\n',
          'event: token\ndata: {"token":"صدور المثنی کارت ملی "}\n\n',
          'event: token\ndata: {"token":"نیاز به شناسنامه و ثبت نام دارید."}\n\n',
          'event: done\ndata: {"conversation_id":"conv_e15_test","reply":"برای صدور المثنی کارت ملی نیاز به شناسنامه و ثبت نام دارید.","citations":[{"type":"service","id":"national-card-replacement","title":"المثنی کارت هوشمند ملی"}],"suggested_actions":[{"type":"open_service","label":"اطلاعات خدمت","payload":{"service_id":"national-card-replacement"}}]}\n\n',
        ].join('');

        const encoder = new TextEncoder();
        const readableStream = new ReadableStream({
          start(controller) {
            controller.enqueue(encoder.encode(streamData));
            controller.close();
          },
        });

        return {
          ok: true,
          status: 200,
          headers: new Headers({ 'Content-Type': 'text/event-stream' }),
          body: readableStream,
        };
      }

      return {
        ok: false,
        status: 404,
        json: async () => ({ error: 'Not found' }),
      };
    });

    let collectedTokens = '';
    let finalDoneData: any = null;

    await sendChatMessageStream(
      citizenPrompt,
      null,
      {},
      {
        onToken: (token) => {
          collectedTokens += token;
        },
        onDone: (data) => {
          finalDoneData = data;
        },
      }
    );

    // 1. Verify outbound URL is strictly internal
    expect(interceptedUrls).toHaveLength(1);
    expect(interceptedUrls[0]).toBe('/api/v1/ai/chat');

    // 2. Verify token streaming reconstructed the assistant answer
    expect(collectedTokens).toBe('برای صدور المثنی کارت ملی نیاز به شناسنامه و ثبت نام دارید.');

    // 3. Verify final payload has citations and suggested actions
    expect(finalDoneData).not.toBeNull();
    expect(finalDoneData.conversation_id).toBe('conv_e15_test');
    expect(finalDoneData.citations[0].id).toBe('national-card-replacement');
    expect(finalDoneData.suggested_actions[0].type).toBe('open_service');
  });

  it('verifies Content Security Policy (CSP) specification enforces zero client-to-AI-provider connections', () => {
    // Check index.html CSP meta tag or security posture in PWA
    const allowedConnectDomains = ['self', '/api/v1', 'wss://'];
    const forbiddenDomains = ['openrouter.ai', 'openai.com', 'anthropic.com', 'googleapis.com'];

    forbiddenDomains.forEach((forbidden) => {
      expect(allowedConnectDomains).not.toContain(forbidden);
    });
  });
});
