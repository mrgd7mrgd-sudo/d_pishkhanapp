export interface AiCitation {
  type: string;
  id: string;
  title?: string | undefined;
}

export interface AiSuggestedAction {
  type: string;
  label: string;
  payload: Record<string, any>;
}

export interface AiUsage {
  model: string;
  input_tokens: number;
  output_tokens: number;
  cost_rials: number;
}

export interface AiChatMessage {
  id: string;
  role: 'user' | 'assistant';
  content: string;
  citations?: AiCitation[] | undefined;
  suggested_actions?: AiSuggestedAction[] | undefined;
  created_at: string;
  isStreaming?: boolean | undefined;
}
