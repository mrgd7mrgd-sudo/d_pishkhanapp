import tsParser from '@typescript-eslint/parser';
import tsPlugin from '@typescript-eslint/eslint-plugin';
import importPlugin from 'eslint-plugin-import';
import reactPlugin from 'eslint-plugin-react';
import reactHooksPlugin from 'eslint-plugin-react-hooks';

export default [
  {
    files: ['**/*.{ts,tsx,js,jsx}'],
    languageOptions: {
      parser: tsParser,
      parserOptions: {
        ecmaVersion: 'latest',
        sourceType: 'module',
        ecmaFeatures: {
          jsx: true,
        },
      },
    },
    plugins: {
      '@typescript-eslint': tsPlugin,
      import: importPlugin,
      react: reactPlugin,
      'react-hooks': reactHooksPlugin,
    },
    rules: {
      'max-lines': ['error', { max: 400, skipBlankLines: true, skipComments: true }],
      'max-lines-per-function': ['error', { max: 60, skipBlankLines: true }],
      complexity: ['error', 12],
      'max-depth': ['error', 4],
      '@typescript-eslint/no-explicit-any': 'error',
      'import/no-restricted-paths': [
        'error',
        {
          zones: [
            {
              target: './src/features',
              from: './src/features',
              except: ['./'],
              message: 'اسلایس‌ها نباید مستقیماً به هم وابسته شوند؛ از shared/ استفاده کنید.',
            },
          ],
        },
      ],
      'no-restricted-syntax': [
        'error',
        {
          selector:
            'JSXAttribute[name.name="className"] Literal[value=/(^|\\s)(ml-|mr-|pl-|pr-|left-|right-|float-left|float-right)/]',
          message:
            'کلاس‌های جهت‌دار فیزیکی ممنوع است؛ از کلاس‌های منطقی (ms-, me-, ps-, pe-, start-, end-) استفاده کنید.',
        },
        {
          selector:
            'JSXAttribute[name.name="className"] TemplateElement[value.raw=/(^|\\s)(ml-|mr-|pl-|pr-|left-|right-|float-left|float-right)/]',
          message:
            'کلاس‌های جهت‌دار فیزیکی ممنوع است؛ از کلاس‌های منطقی (ms-, me-, ps-, pe-, start-, end-) استفاده کنید.',
        },
        {
          selector: 'JSXElement > JSXText[value=/[\\u0600-\\u06FF]/]',
          message: 'رشته فارسی هاردکد در JSX ممنوع است؛ از فایل ترجمه و سامانه i18n استفاده کنید.',
        },
        {
          selector:
            'JSXAttribute[name.name!="dir"][name.name!="lang"] Literal[value=/[\\u0600-\\u06FF]/]',
          message: 'رشته فارسی هاردکد در JSX ممنوع است؛ از فایل ترجمه و سامانه i18n استفاده کنید.',
        },
      ],
    },
    settings: {
      react: {
        version: 'detect',
      },
    },
  },
];
