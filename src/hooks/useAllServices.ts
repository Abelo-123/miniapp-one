import { useQuery } from '@tanstack/react-query';
import { getServices } from '../api';
import type { Service } from '../types';

export function useAllServices() {
    return useQuery<Service[]>({
        queryKey: ['services', 'all'],
        queryFn: async () => {
            const data = await getServices(true);
            return data.map((s: any) => ({
                id: s.service,
                category: s.category,
                name: s.name,
                type: s.type as Service['type'],
                rate: parseFloat(s.rate),
                min: s.min,
                max: s.max,
                averageTime: s.average_time || s.averageTime || '',
                refill: s.refill,
                cancel: s.cancel,
            }));
        },
        staleTime: 60 * 1000, // 1 minute
    });
}
