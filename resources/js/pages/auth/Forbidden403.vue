<template>
    <div class="flex min-h-screen items-center justify-center bg-background px-4">
        <div class="w-full max-w-md space-y-6 text-center">
            <div>
                <h1 class="text-6xl font-bold text-muted-foreground">403</h1>
                <h2 class="mt-4 text-2xl font-semibold">Доступ запрещён</h2>
                <p class="mt-2 text-muted-foreground">
                    У вас нет прав для просмотра этой страницы.
                </p>
            </div>
            <div class="flex flex-col gap-3">
                <router-link
                    to="/login"
                    class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                >
                    Выйти и войти под другим аккаунтом
                </router-link>
                <button
                    v-if="isAuthenticated"
                    @click="handleLogout"
                    class="rounded-md border border-border px-4 py-2 text-sm font-medium hover:bg-muted"
                >
                    Выйти
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useStore } from 'vuex';

export default {
    name: 'Forbidden403',
    setup() {
        const store = useStore();
        const router = useRouter();
        const isAuthenticated = computed(() => store.getters.isAuthenticated);

        const handleLogout = async () => {
            await store.dispatch('logout');
            router.push('/login');
        };

        return { isAuthenticated, handleLogout };
    },
};
</script>
