/**
 * AI Assistance Channel and Message Roles (§6.1, §8.1)
 */
export const AI_CHANNELS = ['chat', 'voice'] as const;
export type AiChannel = (typeof AI_CHANNELS)[number];

export const AI_MESSAGE_ROLES = ['user', 'assistant', 'system'] as const;
export type AiMessageRole = (typeof AI_MESSAGE_ROLES)[number];

export interface AiChannelMeta {
  code: AiChannel;
  label: string;
}

export const AI_CHANNEL_META: Record<AiChannel, AiChannelMeta> = {
  chat: { code: 'chat', label: 'گفتگوی متنی' },
  voice: { code: 'voice', label: 'دستیار صوتی' },
};

export function getAiChannelMeta(channel: AiChannel): AiChannelMeta {
  return AI_CHANNEL_META[channel];
}
