import { useQuery } from '@tanstack/react-query';
import { getServices } from '../api';
import type { Service } from '../types';

export function useServices() {
    return useQuery<Service[]>({
        queryKey: ['services'],
        queryFn: async () => {
            const data = await getServices(true);
            // Transform the Node.js API response to the format used by the frontend
            return data.map((s: any) => ({
                id: s.service || s.id,
                category: s.category,
                name: s.name,
                type: s.type,
                rate: parseFloat(s.rate),
                original_rate: parseFloat(s.original_rate ?? s.rate),
                min: s.min,
                max: s.max,
                averageTime: s.average_time || s.averageTime || '',
                refill: s.refill,
                cancel: s.cancel,
                custom_description: s.custom_description,
            }));
        },
        placeholderData: (prev) => prev, // keeps old data while fetching
    });
}
