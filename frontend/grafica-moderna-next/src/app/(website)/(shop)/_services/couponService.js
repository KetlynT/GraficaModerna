'use server'

import { apiServer } from '@/lib/apiServer';

export const CouponService = {
  validate: async (code) => {
    return await apiServer(`/coupons/validate/${code}`);
  }
};