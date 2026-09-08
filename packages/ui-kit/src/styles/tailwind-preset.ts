import { tokens } from '../tokens';

export const tailwindPreset = {
  theme: {
    extend: {
      colors: {
        brand: tokens.color.brand,
        surface: tokens.color.surface,
        status: tokens.color.status,
        turnOwner: tokens.color.turnOwner,
      },
      borderRadius: tokens.radius,
      spacing: tokens.space,
      transitionDuration: {
        fast: tokens.motion.fast,
        base: tokens.motion.base,
        slow: tokens.motion.slow,
      },
      transitionTimingFunction: {
        liquid: tokens.motion.ease,
      },
      zIndex: tokens.z,
    },
  },
};

export default tailwindPreset;
