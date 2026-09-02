<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Menu as MenuIcon, ShieldCheck } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import BackButton from '@/components/BackButton.vue';
import CookieBanner from '@/components/CookieBanner.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { getInitials } from '@/composables/useInitials';
import { home, login, register } from '@/routes';
import { dashboard as adminDashboard } from '@/routes/admin';

const page = usePage();

const auth = computed(() => page.props.auth);
const navigation = computed(() => page.props.navigation);
const canRegister = computed(
    () => (page.props as { canRegister?: boolean }).canRegister ?? true,
);

const { whenCurrentUrl } = useCurrentUrl();

const isAdmin = computed(() => {
    const role = auth.value.user?.role;

    return role === 'admin' || role === 'super_admin';
});

const activeStyles = 'text-foreground font-semibold';
</script>

<template>
    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <header class="border-b border-border/70 bg-card/40 backdrop-blur">
            <div
                class="mx-auto flex h-16 w-full max-w-6xl items-center gap-4 px-4"
            >
                <BackButton />
                <Link
                    :href="home()"
                    class="flex items-center gap-2 font-semibold tracking-tight"
                    aria-label="CESIZen — accueil"
                >
                    <AppLogoIcon class="size-7 fill-current text-primary" />
                    <span>CESIZen</span>
                </Link>

                <!-- Desktop nav -->
                <nav
                    class="hidden flex-1 items-center gap-1 md:flex"
                    aria-label="Navigation principale"
                >
                    <template v-for="item in navigation.main" :key="item.id">
                        <Link
                            v-if="item.url"
                            :href="item.url"
                            :class="[
                                'rounded-md px-3 py-2 text-sm text-muted-foreground transition-colors hover:bg-accent hover:text-foreground',
                                whenCurrentUrl(item.url, activeStyles),
                            ]"
                        >
                            {{ item.title }}
                        </Link>
                    </template>
                </nav>

                <div class="ml-auto flex items-center gap-2">
                    <template v-if="auth.user">
                        <Link
                            v-if="isAdmin"
                            :href="adminDashboard()"
                            class="hidden items-center gap-1 rounded-md border border-border px-3 py-1.5 text-sm hover:bg-accent md:inline-flex"
                        >
                            <ShieldCheck class="size-4" />
                            Administration
                        </Link>
                        <DropdownMenu>
                            <DropdownMenuTrigger :as-child="true">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="size-9 rounded-full"
                                    aria-label="Menu utilisateur"
                                >
                                    <Avatar class="size-8">
                                        <AvatarFallback
                                            class="bg-primary/10 text-primary"
                                        >
                                            {{ getInitials(auth.user.name) }}
                                        </AvatarFallback>
                                    </Avatar>
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="w-56">
                                <UserMenuContent :user="auth.user" />
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </template>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="rounded-md px-3 py-1.5 text-sm hover:bg-accent"
                        >
                            Connexion
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="register()"
                            class="rounded-md bg-primary px-3 py-1.5 text-sm text-primary-foreground shadow hover:bg-primary/90"
                        >
                            Inscription
                        </Link>
                    </template>

                    <!-- Mobile menu trigger -->
                    <Sheet>
                        <SheetTrigger :as-child="true">
                            <Button
                                variant="ghost"
                                size="icon"
                                class="md:hidden"
                                aria-label="Ouvrir la navigation"
                            >
                                <MenuIcon class="size-5" />
                            </Button>
                        </SheetTrigger>
                        <SheetContent side="left" class="w-72 p-6">
                            <SheetHeader>
                                <SheetTitle>Navigation</SheetTitle>
                            </SheetHeader>
                            <nav class="mt-6 flex flex-col gap-1">
                                <Link
                                    v-for="item in navigation.main"
                                    :key="item.id"
                                    :href="item.url ?? '#'"
                                    :class="[
                                        'rounded-md px-3 py-2 text-sm hover:bg-accent',
                                        whenCurrentUrl(
                                            item.url ?? '',
                                            activeStyles,
                                        ),
                                    ]"
                                >
                                    {{ item.title }}
                                </Link>
                            </nav>
                        </SheetContent>
                    </Sheet>
                </div>
            </div>
        </header>

        <main class="flex-1">
            <slot />
        </main>

        <footer class="border-t border-border/70 bg-card/40">
            <div
                class="mx-auto flex w-full max-w-6xl flex-col gap-4 px-4 py-6 text-sm text-muted-foreground sm:flex-row sm:items-center sm:justify-between"
            >
                <p>
                    © {{ new Date().getFullYear() }} CESIZen — Ministère de la
                    santé et de la prévention
                </p>
                <nav
                    class="flex flex-wrap gap-x-4 gap-y-2"
                    aria-label="Liens secondaires"
                >
                    <Link
                        href="/politique-de-confidentialite"
                        class="hover:text-foreground hover:underline"
                    >
                        Politique de confidentialité
                    </Link>
                    <Link
                        v-for="item in navigation.footer"
                        :key="item.id"
                        :href="item.url ?? '#'"
                        class="hover:text-foreground hover:underline"
                    >
                        {{ item.title }}
                    </Link>
                </nav>
            </div>
        </footer>

        <CookieBanner />
    </div>
</template>
