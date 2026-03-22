import { useState, useEffect, useCallback } from 'react';
import { Plus, Pencil, Trash2, Search, Loader2, Users } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
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
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { usersAPI, rolesAPI, AdminUser, AdminRole } from '@/api';
import { toast } from 'sonner';
import { useAuthStore } from '@/store/authStore';

export function AdminUsers() {
  const currentUserId = useAuthStore((s) => s.user?.id);
  const [users, setUsers] = useState<AdminUser[]>([]);
  const [roles, setRoles] = useState<AdminRole[]>([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [modalOpen, setModalOpen] = useState(false);
  const [editingUser, setEditingUser] = useState<AdminUser | null>(null);
  const [filters, setFilters] = useState({
    search: '',
    role_id: '',
    sort_by: 'id',
    sort_order: 'desc' as 'asc' | 'desc',
    page: 1,
    per_page: 15,
  });
  const [appliedSearch, setAppliedSearch] = useState('');
  const [pagination, setPagination] = useState<{
    current_page: number;
    last_page: number;
    total: number;
    from: number;
    to: number;
  } | null>(null);
  const [form, setForm] = useState({
    name: '',
    email: '',
    password: '',
    roles: [] as number[],
  });

  const applySearch = () => {
    setAppliedSearch(filters.search);
    setFilters((f) => ({ ...f, page: 1 }));
  };

  const fetchUsers = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await usersAPI.getList({
        search: appliedSearch || undefined,
        role_id: filters.role_id ? Number(filters.role_id) : undefined,
        sort_by: filters.sort_by,
        sort_order: filters.sort_order,
        page: filters.page,
        per_page: filters.per_page,
      });
      setUsers(res.data);
      setPagination(res.meta);
    } catch (err: any) {
      setError(err?.response?.data?.message || 'Ошибка загрузки пользователей');
      toast.error('Ошибка загрузки пользователей');
    } finally {
      setLoading(false);
    }
  }, [filters]);

  const fetchRoles = useCallback(async () => {
    try {
      const list = await rolesAPI.getAll();
      setRoles(list);
    } catch (err: any) {
      console.error('Error loading roles:', err);
    }
  }, []);

  useEffect(() => {
    fetchUsers();
  }, [fetchUsers]);

  useEffect(() => {
    fetchRoles();
  }, [fetchRoles]);

  const openCreate = () => {
    setEditingUser(null);
    setForm({ name: '', email: '', password: '', roles: [] });
    setModalOpen(true);
  };

  const openEdit = (user: AdminUser) => {
    setEditingUser(user);
    setForm({
      name: user.name,
      email: user.email,
      password: '',
      roles: user.roles.map((r) => r.id),
    });
    setModalOpen(true);
  };

  const closeModal = () => {
    setModalOpen(false);
    setEditingUser(null);
    setForm({ name: '', email: '', password: '', roles: [] });
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!form.name || !form.email) {
      toast.error('Заполните имя и email');
      return;
    }
    if (!editingUser && !form.password) {
      toast.error('Укажите пароль');
      return;
    }
    if (editingUser && form.password && form.password.length < 8) {
      toast.error('Пароль должен быть не менее 8 символов');
      return;
    }
    setSaving(true);
    try {
      if (editingUser) {
        await usersAPI.update(editingUser.id, {
          name: form.name,
          email: form.email,
          password: form.password || undefined,
          roles: form.roles,
        });
        toast.success('Пользователь обновлён');
      } else {
        await usersAPI.create({
          name: form.name,
          email: form.email,
          password: form.password,
          roles: form.roles,
        });
        toast.success('Пользователь создан');
      }
      closeModal();
      fetchUsers();
    } catch (err: any) {
      const msg = err?.response?.data?.message || err?.response?.data?.errors?.email?.[0] || 'Ошибка сохранения';
      toast.error(msg);
    } finally {
      setSaving(false);
    }
  };

  const handleDelete = async (user: AdminUser) => {
    if (user.id === currentUserId) {
      toast.error('Нельзя удалить самого себя');
      return;
    }
    if (!window.confirm(`Удалить пользователя ${user.name}?`)) return;
    try {
      await usersAPI.delete(user.id);
      toast.success('Пользователь удалён');
      fetchUsers();
    } catch (err: any) {
      toast.error(err?.response?.data?.message || 'Ошибка удаления');
    }
  };

  const toggleRole = (roleId: number) => {
    setForm((prev) => ({
      ...prev,
      roles: prev.roles.includes(roleId)
        ? prev.roles.filter((r) => r !== roleId)
        : [...prev.roles, roleId],
    }));
  };

  return (
    <div className="p-4 lg:p-8">
      <div className="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-foreground flex items-center gap-2">
            <Users className="h-7 w-7" />
            Пользователи админ-панели
          </h1>
          <p className="text-muted-foreground mt-1">
            Добавление и управление пользователями с доступом к админ-панели
          </p>
        </div>
        <Button onClick={openCreate} className="shrink-0">
          <Plus className="h-4 w-4 mr-2" />
          Добавить пользователя
        </Button>
      </div>

      <Card className="mb-4">
        <CardContent className="pt-4">
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <Label className="text-sm">Поиск</Label>
              <div className="relative mt-1">
                <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                <Input
                  placeholder="Имя или email..."
                  value={filters.search}
                  onChange={(e) => setFilters((f) => ({ ...f, search: e.target.value }))}
                  onBlur={applySearch}
                  onKeyDown={(e) => e.key === 'Enter' && applySearch()}
                  className="pl-9"
                />
              </div>
            </div>
            <div>
              <Label className="text-sm">Роль</Label>
              <Select
                value={filters.role_id}
                onValueChange={(v) => setFilters((f) => ({ ...f, role_id: v, page: 1 }))}
              >
                <SelectTrigger className="mt-1">
                  <SelectValue placeholder="Все роли" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">Все роли</SelectItem>
                  {roles.map((r) => (
                    <SelectItem key={r.id} value={String(r.id)}>
                      {r.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div>
              <Label className="text-sm">Сортировка</Label>
              <div className="flex gap-2 mt-1">
                <Select
                  value={filters.sort_by}
                  onValueChange={(v) => setFilters((f) => ({ ...f, sort_by: v }))}
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="id">ID</SelectItem>
                    <SelectItem value="name">Имя</SelectItem>
                    <SelectItem value="email">Email</SelectItem>
                    <SelectItem value="created_at">Дата</SelectItem>
                  </SelectContent>
                </Select>
                <Button
                  variant="outline"
                  size="icon"
                  onClick={() =>
                    setFilters((f) => ({
                      ...f,
                      sort_order: f.sort_order === 'asc' ? 'desc' : 'asc',
                    }))
                  }
                >
                  {filters.sort_order === 'asc' ? '↑' : '↓'}
                </Button>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      {error && (
        <div className="mb-4 p-4 bg-destructive/10 border border-destructive/20 rounded-lg text-destructive">
          {error}
        </div>
      )}

      {loading ? (
        <div className="flex justify-center py-12">
          <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
        </div>
      ) : users.length === 0 ? (
        <Card>
          <CardContent className="py-12 text-center text-muted-foreground">
            Пользователи не найдены
          </CardContent>
        </Card>
      ) : (
        <Card>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>ID</TableHead>
                <TableHead>Имя</TableHead>
                <TableHead>Email</TableHead>
                <TableHead>Роли</TableHead>
                <TableHead>Дата создания</TableHead>
                <TableHead className="text-right">Действия</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {users.map((user) => (
                <TableRow key={user.id}>
                  <TableCell>{user.id}</TableCell>
                  <TableCell className="font-medium">{user.name}</TableCell>
                  <TableCell>{user.email}</TableCell>
                  <TableCell>
                    <div className="flex flex-wrap gap-1">
                      {user.roles.map((r) => (
                        <span
                          key={r.id}
                          className="px-2 py-1 text-xs rounded-md bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300"
                        >
                          {r.name}
                        </span>
                      ))}
                      {user.roles.length === 0 && (
                        <span className="text-muted-foreground text-xs">—</span>
                      )}
                    </div>
                  </TableCell>
                  <TableCell className="text-muted-foreground text-sm">
                    {new Date(user.created_at).toLocaleDateString('ru-RU')}
                  </TableCell>
                  <TableCell className="text-right">
                    <div className="flex justify-end gap-2">
                      <Button variant="outline" size="sm" onClick={() => openEdit(user)}>
                        <Pencil className="h-4 w-4" />
                      </Button>
                      <Button
                        variant="outline"
                        size="sm"
                        onClick={() => handleDelete(user)}
                        disabled={user.id === currentUserId}
                        className="text-destructive hover:text-destructive"
                      >
                        <Trash2 className="h-4 w-4" />
                      </Button>
                    </div>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
          {pagination && pagination.last_page > 1 && (
            <div className="flex items-center justify-between p-4 border-t">
              <p className="text-sm text-muted-foreground">
                Показано {pagination.from}–{pagination.to} из {pagination.total}
              </p>
              <div className="flex gap-2">
                <Button
                  variant="outline"
                  size="sm"
                  disabled={pagination.current_page <= 1}
                  onClick={() => setFilters((f) => ({ ...f, page: f.page - 1 }))}
                >
                  Назад
                </Button>
                <Button
                  variant="outline"
                  size="sm"
                  disabled={pagination.current_page >= pagination.last_page}
                  onClick={() => setFilters((f) => ({ ...f, page: f.page + 1 }))}
                >
                  Вперёд
                </Button>
              </div>
            </div>
          )}
        </Card>
      )}

      <Dialog open={modalOpen} onOpenChange={setModalOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>
              {editingUser ? 'Редактировать пользователя' : 'Добавить пользователя'}
            </DialogTitle>
          </DialogHeader>
          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <Label>Имя</Label>
              <Input
                value={form.name}
                onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))}
                required
              />
            </div>
            <div>
              <Label>Email</Label>
              <Input
                type="email"
                value={form.email}
                onChange={(e) => setForm((f) => ({ ...f, email: e.target.value }))}
                required
              />
            </div>
            <div>
              <Label>
                Пароль
                {editingUser && (
                  <span className="text-muted-foreground font-normal ml-1">(оставьте пустым, чтобы не менять)</span>
                )}
              </Label>
              <Input
                type="password"
                value={form.password}
                onChange={(e) => setForm((f) => ({ ...f, password: e.target.value }))}
                required={!editingUser}
                minLength={8}
                placeholder={editingUser ? '••••••••' : ''}
              />
            </div>
            <div>
              <Label>Роли</Label>
              <div className="border rounded-lg p-3 mt-1 max-h-40 overflow-y-auto space-y-2">
                {roles.map((role) => (
                  <label
                    key={role.id}
                    className="flex items-center gap-2 cursor-pointer hover:bg-muted/50 p-2 rounded"
                  >
                    <Checkbox
                      checked={form.roles.includes(role.id)}
                      onCheckedChange={() => toggleRole(role.id)}
                    />
                    <span className="text-sm">{role.name}</span>
                  </label>
                ))}
                {roles.length === 0 && (
                  <p className="text-sm text-muted-foreground">Роли не загружены</p>
                )}
              </div>
            </div>
            <DialogFooter>
              <Button type="button" variant="outline" onClick={closeModal}>
                Отмена
              </Button>
              <Button type="submit" disabled={saving}>
                {saving && <Loader2 className="h-4 w-4 mr-2 animate-spin" />}
                {editingUser ? 'Сохранить' : 'Создать'}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </div>
  );
}
