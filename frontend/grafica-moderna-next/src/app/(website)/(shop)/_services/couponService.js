'use server'

import { apiServer } from '@/app/lib/apiServer';

export const CouponService = {
  validate: async (code) => {
    return await apiServer(`/coupons/validate/${code}`);
  }
};