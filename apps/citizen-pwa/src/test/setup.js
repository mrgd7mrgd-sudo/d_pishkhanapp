import '@testing-library/jest-dom/vitest';
import 'vitest-axe/extend-expect';
import * as matchers from 'vitest-axe/matchers';
import { expect } from 'vitest';
import 'fake-indexeddb/auto';
import '@/shared/i18n';
expect.extend(matchers);
