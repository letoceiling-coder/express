import { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { Phone } from 'lucide-react';
import { Button } from '@/components/ui/button';

export function CallPage() {
  const [searchParams] = useSearchParams();
  const [phone, setPhone] = useState<string>('');

  useEffect(() => {
    const phoneParam = searchParams.get('phone');
    if (phoneParam) {
      setPhone(phoneParam);
    }
  }, [searchParams]);

  if (!phone) {
    return (
      <div className="min-h-screen bg-background flex items-center justify-center p-4">
        <div className="text-center">
          <p className="text-muted-foreground">Номер телефона не указан</p>
        </div>
      </div>
    );
  }

  // Форматируем номер для отображения
  const formatPhone = (phoneNumber: string): string => {
    // Убираем + и пробелы для форматирования
    const digits = phoneNumber.replace(/\D/g, '');
    if (digits.length === 11 && digits.startsWith('7')) {
      // Формат: +7 (XXX) XXX-XX-XX
      return `+7 (${digits.slice(1, 4)}) ${digits.slice(4, 7)}-${digits.slice(7, 9)}-${digits.slice(9)}`;
    }
    return phoneNumber;
  };

  return (
    <div className="min-h-screen bg-background flex items-center justify-center p-4">
      <div className="w-full max-w-md space-y-6 text-center">
        <div className="space-y-4">
          <div className="flex justify-center">
            <div className="w-20 h-20 rounded-full bg-primary/10 flex items-center justify-center">
              <Phone className="h-10 w-10 text-primary" />
            </div>
          </div>
          
          <div className="space-y-2">
            <p className="text-sm text-muted-foreground">Номер телефона</p>
            <p className="text-3xl font-bold text-foreground">{formatPhone(phone)}</p>
          </div>
        </div>

        <div className="pt-4">
          <a
            href={`tel:${phone}`}
            className="inline-block"
          >
            <Button
              size="lg"
              className="w-full text-lg py-6 h-auto"
            >
              <Phone className="h-5 w-5 mr-2" />
              Позвонить
            </Button>
          </a>
        </div>

        <p className="text-xs text-muted-foreground pt-4">
          Нажмите кнопку "Позвонить" для вызова
        </p>
      </div>
    </div>
  );
}

