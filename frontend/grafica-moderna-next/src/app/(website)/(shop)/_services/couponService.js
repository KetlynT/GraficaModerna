import api from '@/app/(website)/services/api';

export const CouponService = {
  validate: async (code) => {
    try {
      const response = await api.get(`/coupons/validate/${code}`);
      return response.data;
    } catch (error) {
      throw new Error(error.response?.data || "Cupom inválido");
    }
  }
};