import { useState, useEffect } from 'react';
import { Plus, Pencil, Trash2, Image as ImageIcon } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog';
import { bannersAPI } from '@/api';
import { toast } from 'sonner';

interface Banner {
  id: number;
  title: string;
  subtitle?: string;
  image?: string;
  cta_text?: string;
  cta_href?: string;
  is_active: boolean;
  sort_order: number;
}

export function AdminBanners() {
  const [banners, setBanners] = useState<Banner[]>([]);
  const [loading, setLoading] = useState(true);
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editing, setEditing] = useState<Banner | null>(null);
  const [saving, setSaving] = useState(false);
  const [formData, setFormData] = useState({
    title: '',
    subtitle: '',
    image: '',
    cta_text: 'В каталог',
    cta_href: '/#products',
    is_active: true,
    sort_order: 0,
  });
  const [mediaCallback] = useState(() => 'banner-image-' + Math.random().toString(36).slice(2));

  useEffect(() => {
    loadBanners();
  }, []);

  useEffect(() => {
    const handleMessage = (e: MessageEvent) => {
      if (e.data?.type === 'media-selected' && e.data?.callback?.startsWith('banner-image-')) {
        if (e.data.url) setFormData((prev) => ({ ...prev, image: e.data.url }));
      }
    };
    const checkStorage = () => {
      const key = Object.keys(localStorage).find((k) => k.startsWith('media-selected-banner-image-'));
      if (key) {
        try {
          const data = JSON.parse(localStorage.getItem(key) || '{}');
          if (data?.url) {
            setFormData((prev) => ({ ...prev, image: data.url }));
            localStorage.removeItem(key);
          }
        } catch (_) {}
      }
    };
    window.addEventListener('message', handleMessage);
    window.addEventListener('focus', checkStorage);
    const t = setInterval(checkStorage, 500);
    return () => {
      window.removeEventListener('message', handleMessage);
      window.removeEventListener('focus', checkStorage);
      clearInterval(t);
    };
  }, []);

  const loadBanners = async () => {
    setLoading(true);
    try {
      const data = await bannersAPI.getAdmin();
      setBanners(Array.isArray(data) ? data : []);
    } catch (e: any) {
      toast.error('Ошибка загрузки баннеров');
    } finally {
      setLoading(false);
    }
  };

  const resetForm = () => {
    setFormData({
      title: '',
      subtitle: '',
      image: '',
      cta_text: 'В каталог',
      cta_href: '/#products',
      is_active: true,
      sort_order: banners.length,
    });
    setEditing(null);
  };

  const openCreate = () => {
    resetForm();
    setFormData((prev) => ({ ...prev, sort_order: banners.length }));
    setIsDialogOpen(true);
  };

  const openEdit = (b: Banner) => {
    setEditing(b);
    setFormData({
      title: b.title,
      subtitle: b.subtitle || '',
      image: b.image || '',
      cta_text: b.cta_text || 'В каталог',
      cta_href: b.cta_href || '/#products',
      is_active: b.is_active ?? true,
      sort_order: b.sort_order ?? 0,
    });
    setIsDialogOpen(true);
  };

  const handleSave = async () => {
    if (!formData.title.trim()) {
      toast.error('Введите заголовок');
      return;
    }
    setSaving(true);
    try {
      if (editing) {
        await bannersAPI.update(editing.id, formData);
        toast.success('Баннер обновлён');
      } else {
        await bannersAPI.create(formData);
        toast.success('Баннер создан');
      }
      setIsDialogOpen(false);
      loadBanners();
    } catch (e: any) {
      toast.error(e?.response?.data?.message || 'Ошибка сохранения');
    } finally {
      setSaving(false);
    }
  };

  const handleDelete = async (b: Banner) => {
    if (!confirm('Удалить баннер?')) return;
    try {
      await bannersAPI.delete(b.id);
      toast.success('Баннер удалён');
      loadBanners();
    } catch (e: any) {
      toast.error('Ошибка удаления');
    }
  };

  const openMediaSelector = () => {
    window.open('/admin/media?select=true&callback=' + mediaCallback, '_blank');
  };

  if (loading) {
    return (
      <div className="p-4 lg:p-8 flex items-center justify-center min-h-[400px]">
        <p className="text-muted-foreground">Загрузка...</p>
      </div>
    );
  }

  return (
    <div className="p-4 lg:p-8">
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold">Баннеры</h1>
          <p className="text-muted-foreground text-sm">Управление слайдами главной страницы</p>
        </div>
        <Button onClick={openCreate}>
          <Plus className="h-4 w-4 mr-2" />
          Добавить
        </Button>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Список баннеров</CardTitle>
        </CardHeader>
        <CardContent>
          {banners.length === 0 ? (
            <p className="text-muted-foreground py-8 text-center">
              Нет баннеров. Добавьте первый — он появится в HeroSlider на главной.
            </p>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Изображение</TableHead>
                  <TableHead>Заголовок</TableHead>
                  <TableHead>Подзаголовок</TableHead>
                  <TableHead>Порядок</TableHead>
                  <TableHead>Вкл.</TableHead>
                  <TableHead className="w-[100px]">Действия</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {banners
                  .sort((a, b) => (a.sort_order ?? 0) - (b.sort_order ?? 0))
                  .map((b) => (
                    <TableRow key={b.id}>
                      <TableCell>
                        {b.image ? (
                          <img
                            src={b.image}
                            alt=""
                            className="h-12 w-20 object-cover rounded"
                          />
                        ) : (
                          <span className="text-muted-foreground text-xs">—</span>
                        )}
                      </TableCell>
                      <TableCell className="font-medium">{b.title}</TableCell>
                      <TableCell className="text-muted-foreground text-sm max-w-[200px] truncate">
                        {b.subtitle || '—'}
                      </TableCell>
                      <TableCell>{b.sort_order ?? 0}</TableCell>
                      <TableCell>
                        <Switch checked={b.is_active ?? true} disabled />
                      </TableCell>
                      <TableCell>
                        <div className="flex gap-2">
                          <Button variant="ghost" size="icon" onClick={() => openEdit(b)}>
                            <Pencil className="h-4 w-4" />
                          </Button>
                          <Button variant="ghost" size="icon" onClick={() => handleDelete(b)}>
                            <Trash2 className="h-4 w-4 text-destructive" />
                          </Button>
                        </div>
                      </TableCell>
                    </TableRow>
                  ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>

      <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
        <DialogContent className="max-w-lg">
          <DialogHeader>
            <DialogTitle>{editing ? 'Редактировать баннер' : 'Новый баннер'}</DialogTitle>
          </DialogHeader>
          <div className="space-y-4 py-4">
            <div>
              <Label>Заголовок</Label>
              <Input
                value={formData.title}
                onChange={(e) => setFormData((p) => ({ ...p, title: e.target.value }))}
                placeholder="Свежая выпечка каждый день"
              />
            </div>
            <div>
              <Label>Подзаголовок</Label>
              <Input
                value={formData.subtitle}
                onChange={(e) => setFormData((p) => ({ ...p, subtitle: e.target.value }))}
                placeholder="Печём с душой"
              />
            </div>
            <div>
              <Label>Изображение</Label>
              <div className="flex gap-2 items-center">
                <Input
                  value={formData.image}
                  onChange={(e) => setFormData((p) => ({ ...p, image: e.target.value }))}
                  placeholder="URL или выберите из медиа"
                />
                <Button type="button" variant="outline" size="icon" onClick={openMediaSelector}>
                  <ImageIcon className="h-4 w-4" />
                </Button>
              </div>
              {formData.image && (
                <img
                  src={formData.image}
                  alt=""
                  className="mt-2 h-24 object-cover rounded border"
                />
              )}
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div>
                <Label>Текст кнопки</Label>
                <Input
                  value={formData.cta_text}
                  onChange={(e) => setFormData((p) => ({ ...p, cta_text: e.target.value }))}
                  placeholder="В каталог"
                />
              </div>
              <div>
                <Label>Ссылка кнопки</Label>
                <Input
                  value={formData.cta_href}
                  onChange={(e) => setFormData((p) => ({ ...p, cta_href: e.target.value }))}
                  placeholder="/#products"
                />
              </div>
            </div>
            <div className="flex items-center justify-between">
              <div>
                <Label>Порядок</Label>
                <Input
                  type="number"
                  min={0}
                  value={formData.sort_order}
                  onChange={(e) =>
                    setFormData((p) => ({ ...p, sort_order: parseInt(e.target.value) || 0 }))
                  }
                  className="w-20"
                />
              </div>
              <div className="flex items-center gap-2">
                <Label>Включён</Label>
                <Switch
                  checked={formData.is_active}
                  onCheckedChange={(v) => setFormData((p) => ({ ...p, is_active: !!v }))}
                />
              </div>
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setIsDialogOpen(false)}>
              Отмена
            </Button>
            <Button onClick={handleSave} disabled={saving}>
              {saving ? 'Сохранение...' : 'Сохранить'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
