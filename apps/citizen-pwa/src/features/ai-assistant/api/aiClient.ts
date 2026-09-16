import type { AiChatMessage, AiSuggestedAction, AiCitation, AiUsage } from '../types';

export interface StreamCallbacks {
  onMeta?: (meta: { conversation_id: string; intent: string; confidence: number }) => void;
  onToken?: (token: string) => void;
  onDone?: (payload: {
    conversation_id: string;
    message_id: string;
    reply: string;
    citations?: AiCitation[];
    suggested_actions?: AiSuggestedAction[];
    usage?: AiUsage;
  }) => void;
  onError?: (error: Error) => void;
}

export async function sendChatMessageStream(
  message: string,
  conversationId?: string | null,
  context?: Record<string, any>,
  callbacks?: StreamCallbacks
): Promise<void> {
  const token = localStorage.getItem('auth_token');
  const headers: Record<string, string> = {
    'Content-Type': 'application/json',
    'Accept': 'text/event-stream',
  };
  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }

  try {
    const response = await fetch('/api/v1/ai/chat', {
      method: 'POST',
      headers,
      body: JSON.stringify({
        conversation_id: conversationId ?? null,
        message,
        context: context ?? {},
      }),
    });

    if (!response.ok) {
      const errData = await response.json().catch(() => ({}));
      throw new Error(errData.message || 'خطا در برقراری ارتباط با دستیار هوشمند');
    }

    if (!response.body) {
      throw new Error('ReadableStream not supported');
    }

    const reader = response.body.getReader();
    const decoder = new TextDecoder('utf-8');
    let buffer = '';

    while (true) {
      const { done, value } = await reader.read();
      if (done) break;

      buffer += decoder.decode(value, { stream: true });
      const lines = buffer.split('\n\n');
      buffer = lines.pop() || '';

      for (const block of lines) {
        if (!block.trim()) continue;

        let event = 'message';
        let dataStr = '';

        const blockLines = block.split('\n');
        for (const line of blockLines) {
          if (line.startsWith('event:')) {
            event = line.replace('event:', '').trim();
          } else if (line.startsWith('data:')) {
            dataStr = line.replace('data:', '').trim();
          }
        }

        if (!dataStr) continue;

        try {
          const parsed = JSON.parse(dataStr);
          if (event === 'meta' && callbacks?.onMeta) {
            callbacks.onMeta(parsed);
          } else if (event === 'token' && callbacks?.onToken) {
            callbacks.onToken(parsed.chunk ?? parsed.token ?? '');
          } else if (event === 'done' && callbacks?.onDone) {
            callbacks.onDone(parsed);
          }
        } catch {
          // ignore non-json chunk
        }
      }
    }
  } catch (err: any) {
    callbacks?.onError?.(err instanceof Error ? err : new Error(String(err)));
  }
}

export async function sendVoiceMessage(
  audioBlob: Blob,
  durationSeconds: number = 5,
  conversationId?: string | null,
  context?: Record<string, any>
): Promise<{
  transcript: string;
  reply: string;
  citations?: AiCitation[];
  suggested_actions?: AiSuggestedAction[];
  conversation_id: string;
  usage?: AiUsage;
}> {
  const token = localStorage.getItem('auth_token');
  const headers: Record<string, string> = {};
  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }

  const formData = new FormData();
  formData.append('audio', audioBlob, 'recording.webm');
  formData.append('duration_seconds', String(durationSeconds));
  if (conversationId) {
    formData.append('conversation_id', conversationId);
  }
  if (context) {
    formData.append('context', JSON.stringify(context));
  }

  const res = await fetch('/api/v1/ai/voice', {
    method: 'POST',
    headers,
    body: formData,
  });

  if (!res.ok) {
    const err = await res.json().catch(() => ({}));
    throw new Error(err.message || 'خطا در پردازش فایل صوتی');
  }

  const json = await res.json();
  return json.data;
}
