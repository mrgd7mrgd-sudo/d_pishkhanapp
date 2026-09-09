import React, { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Input, Button } from '@pishkhan/ui-kit';
import { normalizeDigits } from '@pishkhan/domain';
import { useOperatorAuthStore } from '../model/useOperatorAuthStore';

const CredentialsHeader: React.FC = () => {
  const { t } = useTranslation();
  return (
    <div className="space-y-1 mb-4">
      <h2 id="operator-login-heading" className="text-xl font-bold text-slate-900 dark:text-slate-100">
        {t('auth.login_title')}
      </h2>
      <p className="text-sm text-slate-600 dark:text-slate-400">
        {t('auth.login_subtitle')}
      </p>
    </div>
  );
};

interface InputsProps {
  officeCode: string;
  username: string;
  password: string;
  isLoading: boolean;
  onOfficeCodeChange: (val: string) => void;
  onUsernameChange: (val: string) => void;
  onPasswordChange: (val: string) => void;
}

const CredentialsInputs: React.FC<InputsProps> = ({
  officeCode,
  username,
  password,
  isLoading,
  onOfficeCodeChange,
  onUsernameChange,
  onPasswordChange,
}) => {
  const { t } = useTranslation();
  return (
    <>
      <Input
        id="office-code-input"
        type="text"
        dir="ltr"
        label={t('auth.office_code_label')}
        placeholder={t('auth.office_code_placeholder')}
        value={officeCode}
        onChange={(e) => onOfficeCodeChange(normalizeDigits(e.target.value))}
        disabled={isLoading}
        autoFocus
      />
      <Input
        id="username-input"
        type="text"
        dir="ltr"
        label={t('auth.username_label')}
        placeholder={t('auth.username_placeholder')}
        value={username}
        onChange={(e) => onUsernameChange(e.target.value)}
        disabled={isLoading}
      />
      <Input
        id="password-input"
        type="password"
        dir="ltr"
        label={t('auth.password_label')}
        placeholder={t('auth.password_placeholder')}
        value={password}
        onChange={(e) => onPasswordChange(e.target.value)}
        disabled={isLoading}
      />
    </>
  );
};

export const CredentialsStep: React.FC = () => {
  const { t } = useTranslation();
  const store = useOperatorAuthStore();
  const [localOfficeCode, setLocalOfficeCode] = useState(store.officeCode);
  const [localUsername, setLocalUsername] = useState(store.username);
  const [localPassword, setLocalPassword] = useState(store.password);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    await store.submitCredentials(localOfficeCode, localUsername, localPassword);
  };

  const errorMessage = store.error === 'invalid_credentials'
    ? t('auth.invalid_credentials')
    : store.error;

  return (
    <form onSubmit={handleSubmit} className="space-y-4" aria-labelledby="operator-login-heading">
      <CredentialsHeader />
      <CredentialsInputs
        officeCode={localOfficeCode}
        username={localUsername}
        password={localPassword}
        isLoading={store.isLoading}
        onOfficeCodeChange={setLocalOfficeCode}
        onUsernameChange={setLocalUsername}
        onPasswordChange={setLocalPassword}
      />
      {errorMessage && (
        <p role="alert" className="text-xs text-rose-600 font-medium">
          {errorMessage}
        </p>
      )}
      <Button type="submit" variant="primary" className="w-full" isLoading={store.isLoading}>
        {t('auth.login_button')}
      </Button>
    </form>
  );
};
