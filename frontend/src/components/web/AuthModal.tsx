import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { X, Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { authAPI } from '@/api';
import { useAuthStore } from '@/store/authStore';
import { toast } from 'sonner';

const RESEND_COOLDOWN_SEC = 60;

const formatPhone = (v: string): string => {
  const d = v.replace(/\D/g, '');
  const norm = d.startsWith('8') ? '7' + d.slice(1) : d;
  const limited = norm.slice(0, 11);
  if (limited.length <= 1) return limited ? `+${limited}` : '';
  if (limited.length <= 4) return `+${limited[0]} (${limited.slice(1)}`;
  if (limited.length <= 7) return `+${limited[0]} (${limited.slice(1, 4)}) ${limited.slice(4)}`;
  if (limited.length <= 9) return `+${limited[0]} (${limited.slice(1, 4)}) ${limited.slice(4, 7)}-${limited.slice(7)}`;
  return `+${limited[0]} (${limited.slice(1, 4)}) ${limited.slice(4, 7)}-${limited.slice(7, 9)}-${limited.slice(9)}`;
};

const getPhoneDigits = (v: string): string => {
  const d = v.replace(/\D/g, '');
  return d.startsWith('8') ? '7' + d.slice(1) : d;
};

interface AuthModalProps {
  onClose: () => void;
  onSuccess: () => void;
  /** Optional: redirect to this path after login (e.g. /checkout). Cart state is preserved via persist. */
  returnTo?: string;
}

/** В dev бэкенд принимает код 123456 без отправки SMS */

export function AuthModal({ onClose, onSuccess, returnTo }: AuthModalProps) {
  const [step, setStep] = useState<'phone' | 'code'>('phone');
  const [phone, setPhone] = useState('');
  const [code, setCode] = useState('');
  const [loading, setLoading] = useState(false);
  const [resendCooldown, setResendCooldown] = useState(0);
  const setAuth = useAuthStore((s) => s.setAuth);
  const navigate = useNavigate();

  useEffect(() => {
    if (resendCooldown <= 0) return;
    const t = setInterval(() => setResendCooldown((c) => Math.max(0, c - 1)), 1000);
    return () => clearInterval(t);
  }, [resendCooldown]);

  const handleSendCode = async () => {
    const digits = getPhoneDigits(phone);
    if (digits.length < 11) {
      toast.error('Введите корректный номер телефона');
      return;
    }
    setLoading(true);
    try {
      if (import.meta.env.DEV) {
        toast.success('В dev: введите код 123456');
        setStep('code');
        setResendCooldown(RESEND_COOLDOWN_SEC);
      } else {
        await authAPI.sendCode(phone);
        toast.success('Код отправлен на указанный номер');
        setStep('code');
        setResendCooldown(RESEND_COOLDOWN_SEC);
      }
    } catch (e: any) {
      toast.error('Ошибка отправки SMS');
    } finally {
      setLoading(false);
    }
  };

  const handleResend = async () => {
    if (resendCooldown > 0) return;
    await handleSendCode();
  };

  const handleVerify = async () => {
    const digits = getPhoneDigits(phone);
    const codeToUse = code || '';
    if (digits.length < 11 || codeToUse.length < 4) {
      toast.error('Введите код из SMS');
      return;
    }
    setLoading(true);
    try {
      const res = await authAPI.verifyCode(phone, codeToUse);
      const u = res.user || {};
      setAuth(res.token, {
        id: u.id,
        name: u.name || 'User',
        email: u.email || '',
        phone: u.phone,
      });
      toast.success('Вход выполнен');
      onSuccess();
      onClose();
      if (returnTo) navigate(returnTo);
    } catch (e: any) {
      toast.error(e?.response?.data?.message || e?.message || 'Неверный код');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4">
      <div className="w-full max-w-sm rounded-2xl bg-background p-6 shadow-xl">
        <div className="flex justify-between items-center mb-4">
          <h2 className="text-lg font-semibold">Вход по номеру телефона</h2>
          <button onClick={onClose} className="p-2 -mr-2 rounded-lg hover:bg-muted">
            <X className="h-5 w-5" />
          </button>
        </div>

        {step === 'phone' ? (
          <>
            <p className="text-xs text-muted-foreground mb-2">Проверьте правильность номера</p>
            <input
              type="tel"
              placeholder="+7 (___) ___-__-__"
              value={phone}
              onChange={(e) => setPhone(formatPhone(e.target.value))}
              className="w-full rounded-xl border border-border bg-muted px-4 py-3 text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary mb-4"
            />
            {import.meta.env.DEV && (
              <p className="text-xs text-muted-foreground mb-2">В dev: код 123456 (без SMS)</p>
            )}
            <Button onClick={handleSendCode} disabled={loading} className="w-full">
              {loading ? <Loader2 className="h-4 w-4 animate-spin" /> : 'Получить код'}
            </Button>
          </>
        ) : (
          <>
            <p className="text-sm text-muted-foreground mb-2">Код отправлен на {phone}</p>
            <p className="text-xs text-muted-foreground mb-2">SMS может идти до 30 секунд</p>
            <input
              type="text"
              inputMode="numeric"
              placeholder="Код из SMS"
              value={code}
              onChange={(e) => setCode(e.target.value.replace(/\D/g, '').slice(0, 6))}
              className="w-full rounded-xl border border-border bg-muted px-4 py-3 text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary mb-4"
            />
            <Button onClick={handleVerify} disabled={loading} className="w-full">
              {loading ? <Loader2 className="h-4 w-4 animate-spin" /> : 'Войти'}
            </Button>
            <div className="flex justify-between items-center mt-2">
              <button
                onClick={() => { setStep('phone'); setResendCooldown(0); }}
                className="text-sm text-muted-foreground hover:text-foreground"
              >
                Изменить номер
              </button>
              <button
                onClick={handleResend}
                disabled={resendCooldown > 0 || loading}
                className="text-sm font-medium text-primary disabled:text-muted-foreground disabled:cursor-not-allowed hover:underline"
              >
                {resendCooldown > 0 ? `Отправить повторно через ${resendCooldown} сек` : 'Отправить код повторно'}
              </button>
            </div>
          </>
        )}
      </div>
    </div>
  );
}
