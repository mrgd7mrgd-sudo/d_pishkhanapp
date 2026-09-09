import '@testing-library/jest-dom/vitest';
import 'vitest-axe/extend-expect';
import * as matchers from 'vitest-axe/matchers';
import { expect } from 'vitest';
import 'fake-indexeddb/auto';
import '@/shared/i18n';

declare module 'vitest' {
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  export interface Assertion<T = any> {
    toHaveNoViolations(): T;
  }
}

expect.extend(matchers);
