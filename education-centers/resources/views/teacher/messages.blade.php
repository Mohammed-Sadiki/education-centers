@extends('layouts.communication')

@section('title', __('Messages reçus') . ' — ' . config('app.name'))

@section('content')
<div class="flex min-h-0 flex-1 overflow-hidden bg-slate-100 dark:bg-slate-950"
     x-data="teacherMessagesApp()"
     x-init="init()"
     id="teacher-messages-app">

    {{-- ═══════════════════════════════════════════════════════════════════════
         PANNEAU GAUCHE — LISTE DES MESSAGES REÇUS (lecture seule)
    ══════════════════════════════════════════════════════════════════════════ --}}
    <aside class="flex w-full shrink-0 flex-col border-e border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 sm:w-[32%] sm:min-w-[320px] sm:max-w-[440px]"
           :class="activeConversationId && isMobile ? 'hidden' : 'flex'">

        {{-- En-tête du panneau --}}
        <div class="shrink-0 space-y-3 border-b border-slate-100 p-4 dark:border-slate-800">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-base font-bold tracking-tight text-slate-900 dark:text-white">{{ __('Messages reçus') }}</h1>
                    <p class="text-xs text-slate-400" x-text="totalUnread > 0 ? totalUnread + ' ' + @js(__('message(s) non lu(s)')) : @js(__('Tous vos messages sont lus'))"></p>
                </div>
                {{-- Indication discrète : lecture seule --}}
                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                    {{ __('Lecture seule') }}
                </span>
            </div>

            {{-- Filtres pertinents pour un professeur --}}
            <div class="flex flex-wrap items-center gap-1.5" role="group" aria-label="{{ __('Filtrer les messages') }}">
                <template x-for="f in filters" :key="f.key">
                    <button type="button"
                            @click="activeFilter = f.key"
                            class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-semibold transition"
                            :class="activeFilter === f.key
                                ? 'border-brand-500 bg-brand-50 text-brand-700 dark:border-brand-500/50 dark:bg-brand-950/40 dark:text-brand-300'
                                : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700'"
                            :aria-pressed="activeFilter === f.key">
                        <span x-text="f.label"></span>
                        <span x-show="f.count > 0"
                              x-text="f.count"
                              class="inline-flex h-4 min-w-4 items-center justify-center rounded-full px-1 text-[10px] font-bold"
                              :class="f.key === 'unread' ? 'bg-red-500 text-white' : (activeFilter === f.key ? 'bg-brand-600 text-white' : 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300')"></span>
                    </button>
                </template>
            </div>

            {{-- Recherche (partagée avec la topbar) --}}
            <div class="relative">
                <svg class="pointer-events-none absolute start-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <input type="search"
                       x-model="$store.comm.search"
                       placeholder="{{ __('Rechercher un message…') }}"
                       class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 ps-9 pe-4 text-sm placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:placeholder:text-slate-500"
                       aria-label="{{ __('Rechercher un message') }}">
            </div>
        </div>

        {{-- Liste des messages reçus --}}
        <nav class="min-h-0 flex-1 overflow-y-auto" aria-label="{{ __('Liste des messages reçus') }}">
            <template x-if="loading && conversations.length === 0">
                <div class="space-y-3 p-4" role="status">
                    <span class="sr-only">{{ __('Chargement…') }}</span>
                    <template x-for="i in 5" :key="i">
                        <div class="ec-skeleton h-14 w-full"></div>
                    </template>
                </div>
            </template>

            <template x-if="!loading && filteredConversations.length === 0">
                <div class="flex flex-col items-center justify-center px-4 py-16 text-center text-slate-400">
                    <svg class="mb-3 h-10 w-10 opacity-40" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                    </svg>
                    <p class="text-sm font-semibold text-slate-600 dark:text-slate-300">{{ __('Aucun message reçu') }}</p>
                    <p class="mt-1 text-xs">{{ __('Les communications de votre direction apparaîtront ici.') }}</p>
                </div>
            </template>

            <ul>
                <template x-for="conv in filteredConversations" :key="conv.id">
                    <li>
                        <button type="button"
                                @click="openConversation(conv.id)"
                                class="relative flex w-full items-start gap-3 border-b border-slate-50 px-4 py-3 text-start transition-colors hover:bg-slate-50 dark:border-slate-800/60 dark:hover:bg-slate-800/40"
                                :class="activeConversationId === conv.id ? 'bg-brand-50/70 dark:bg-brand-950/30' : ''"
                                :aria-current="activeConversationId === conv.id ? 'true' : null">
                            <span x-show="activeConversationId === conv.id" class="absolute inset-y-0 start-0 w-1 bg-gradient-to-b from-brand-500 to-indigo-500"></span>

                            {{-- Avatar expéditeur avec initiales --}}
                            <div class="mt-0.5 flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-sm font-bold uppercase text-white shadow-2xs"
                                 :class="avatarClass(conv.other_user.name)"
                                 x-text="initials(conv.other_user.name)"></div>

                            {{-- Expéditeur, rôle, centre, aperçu --}}
                            <div class="min-w-0 flex-1">
                                <div class="flex items-baseline justify-between gap-2">
                                    <p class="truncate text-sm text-slate-900 dark:text-white"
                                       :class="conv.unread_count > 0 ? 'font-bold' : 'font-semibold'"
                                       x-text="conv.other_user.name"></p>
                                    <span class="shrink-0 text-[11px] text-slate-400"
                                          x-text="conv.last_message ? conv.last_message.time : ''"></span>
                                </div>
                                <p class="mt-0.5 truncate text-xs text-slate-500 dark:text-slate-400"
                                   x-text="conv.other_user.role + (conv.other_user.center_name ? ' · ' + conv.other_user.center_name : '')"></p>
                                <div class="mt-1 flex items-center justify-between gap-2">
                                    <p class="truncate text-xs"
                                       :class="conv.unread_count > 0 ? 'font-semibold text-slate-700 dark:text-slate-200' : 'text-slate-400'"
                                       x-text="conv.last_message ? conv.last_message.content : ''"></p>
                                    <span x-show="conv.unread_count > 0"
                                          x-text="conv.unread_count"
                                          class="flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-red-500 px-1.5 text-[10px] font-bold text-white"></span>
                                </div>
                            </div>
                        </button>
                    </li>
                </template>
            </ul>
        </nav>
    </aside>

    {{-- ═══════════════════════════════════════════════════════════════════════
         PANNEAU DROIT — LECTURE DES MESSAGES (AUCUN CHAMP D'ENVOI NI RÉPONSE)
    ══════════════════════════════════════════════════════════════════════════ --}}
    <section class="flex min-w-0 flex-1 flex-col bg-[#F7F8FC] dark:bg-slate-950"
             :class="!activeConversationId && isMobile ? 'hidden' : 'flex'">

        {{-- Aucun message sélectionné --}}
        <template x-if="!activeConversationId">
            <div class="flex h-full flex-col items-center justify-center px-8 text-center text-slate-400">
                <div class="mb-4 flex h-20 w-20 items-center justify-center rounded-3xl bg-white shadow-2xs dark:bg-slate-800">
                    <svg class="h-10 w-10 text-brand-300 dark:text-brand-700" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                    </svg>
                </div>
                <p class="text-base font-semibold text-slate-600 dark:text-slate-300">{{ __('Sélectionnez un message à lire') }}</p>
                <p class="mt-2 text-sm text-slate-400">{{ __('Choisissez une communication dans la liste de gauche pour en afficher l\'intégralité.') }}</p>
            </div>
        </template>

        {{-- Message actif en lecture seule --}}
        <template x-if="activeConversationId">
            <div class="flex h-full min-h-0 flex-col">

                {{-- Header : expéditeur --}}
                <div class="flex shrink-0 items-center gap-3 border-b border-slate-200/80 bg-white px-4 py-3 shadow-2xs dark:border-slate-800 dark:bg-slate-900 sm:px-6">
                    {{-- Retour à la liste (mobile) --}}
                    <button type="button"
                            @click="activeConversationId = null; currentMessages = []; currentOther = null"
                            class="rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 sm:hidden"
                            aria-label="{{ __('Retour à la liste des messages') }}">
                        <svg class="h-5 w-5 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                        </svg>
                    </button>

                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-sm font-bold uppercase text-white shadow-2xs"
                         :class="currentOther ? avatarClass(currentOther.name) : 'bg-slate-400'"
                         x-text="currentOther ? initials(currentOther.name) : '?'"></div>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="truncate text-sm font-bold text-slate-900 dark:text-white" x-text="currentOther ? currentOther.name : '…'"></p>
                            <span class="rounded-full bg-brand-100 px-2 py-0.5 text-[10px] font-semibold text-brand-700 dark:bg-brand-900/40 dark:text-brand-300"
                                  x-text="currentOther ? currentOther.role : ''"></span>
                        </div>
                        <p class="truncate text-xs text-slate-500 dark:text-slate-400" x-text="currentOther ? currentOther.center_name : ''"></p>
                    </div>

                    <span class="hidden items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600 md:inline-flex dark:bg-slate-800 dark:text-slate-300">
                        <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                        </svg>
                        <span>{{ __('Communication en lecture seule') }}</span>
                    </span>
                </div>

                {{-- Zone de messages (défilement indépendant) --}}
                <div class="min-h-0 flex-1 overflow-y-auto px-4 py-5 sm:px-6" id="messages-scroll-area" x-ref="scrollArea">
                    <template x-if="loadingMessages">
                        <div class="space-y-3 py-4" role="status">
                            <span class="sr-only">{{ __('Chargement…') }}</span>
                            <template x-for="i in 3" :key="i">
                                <div class="ec-skeleton h-16 w-3/4"></div>
                            </template>
                        </div>
                    </template>

                    <template x-if="!loadingMessages && currentMessages.length === 0">
                        <div class="flex flex-col items-center justify-center py-12 text-slate-400">
                            <p class="text-sm">{{ __('Aucun message dans cette communication.') }}</p>
                        </div>
                    </template>

                    <template x-for="(msg, index) in currentMessages" :key="msg.id">
                        <div>
                            {{-- Séparateur de date si nouvelle journée --}}
                            <template x-if="index === 0 || currentMessages[index-1].date !== msg.date">
                                <div class="my-5 flex items-center">
                                    <div class="flex-1 border-t border-slate-200 dark:border-slate-700/60"></div>
                                    <span class="mx-3 rounded-full bg-white px-3 py-1 text-[11px] font-semibold text-slate-500 shadow-2xs dark:bg-slate-800 dark:text-slate-400" x-text="msg.date"></span>
                                    <div class="flex-1 border-t border-slate-200 dark:border-slate-700/60"></div>
                                </div>
                            </template>

                            {{-- Bulle du message reçu (toujours alignée à gauche) --}}
                            <div class="mb-3 flex justify-start">
                                <div class="max-w-[85%] sm:max-w-[70%]">
                                    <div class="mb-1 flex items-baseline gap-2 text-xs">
                                        <span class="font-bold text-slate-900 dark:text-white" x-text="msg.sender_name"></span>
                                        <span class="text-[10px] text-slate-400" x-text="msg.sender_role + ' · ' + msg.sender_center"></span>
                                    </div>

                                    <template x-if="msg.content">
                                        <div class="rounded-2xl rounded-tl-md bg-white p-4 text-sm text-slate-800 shadow-2xs ring-1 ring-slate-200/80 dark:bg-slate-800 dark:text-slate-100 dark:ring-slate-700">
                                            <p class="leading-relaxed whitespace-pre-wrap" x-text="msg.content"></p>
                                        </div>
                                    </template>

                                    {{-- Pièces jointes --}}
                                    <template x-if="msg.attachments && msg.attachments.length > 0">
                                        <div class="mt-1.5 space-y-1.5">
                                            <template x-for="att in msg.attachments" :key="att.id">
                                                <div>
                                                    <template x-if="att.is_image">
                                                        <a :href="att.url" target="_blank" rel="noopener"
                                                           class="block overflow-hidden rounded-xl border border-slate-200 shadow-2xs dark:border-slate-700">
                                                            <img :src="att.url" :alt="att.name" class="max-h-60 w-auto rounded-xl object-cover" />
                                                            <p class="px-3 py-1 text-[10px] text-slate-400" x-text="att.name + ' (' + att.size + ')'"></p>
                                                        </a>
                                                    </template>
                                                    <template x-if="!att.is_image">
                                                        <a :href="att.url"
                                                           class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm shadow-2xs transition-colors hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700/60">
                                                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-500 dark:bg-red-900/30 dark:text-red-400">
                                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                                                </svg>
                                                            </span>
                                                            <span class="min-w-0 flex-1">
                                                                <span class="block truncate font-semibold text-slate-700 dark:text-slate-200" x-text="att.name"></span>
                                                                <span class="block text-xs text-slate-400" x-text="att.size"></span>
                                                            </span>
                                                            <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                                            </svg>
                                                        </a>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>
                                    </template>

                                    <p class="mt-1 px-1 text-[10px] text-slate-400" x-text="msg.time"></p>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Note informative en bas (strictement aucun champ de saisie ni bouton d'envoi) --}}
                <div class="shrink-0 border-t border-slate-200/80 bg-white px-5 py-3 text-center text-xs text-slate-500 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400">
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                        </svg>
                        {{ __('Cet espace est un canal d\'information officiel en lecture seule. Pour toute demande relative à vos cours, contactez l\'administration de votre centre.') }}
                    </span>
                </div>

            </div>
        </template>
    </section>
</div>

@push('scripts')
<script>
function teacherMessagesApp() {
    return {
        conversations: [],
        currentMessages: [],
        currentOther: null,
        activeConversationId: {{ $activeConversationId ? (int)$activeConversationId : 'null' }},
        loading: true,
        loadingMessages: false,
        totalUnread: 0,
        activeFilter: 'all',
        pollingInterval: null,
        isMobile: window.innerWidth < 640,

        translations: {
            filterAll: @js(__('Toutes')),
            filterUnread: @js(__('Non lues')),
        },

        // Filtres pertinents pour un professeur (lecture seule)
        get filters() {
            const unread = this.conversations.filter(c => c.unread_count > 0).length;
            return [
                { key: 'all', label: this.translations.filterAll, count: this.conversations.length },
                { key: 'unread', label: this.translations.filterUnread, count: unread },
            ];
        },

        get filteredConversations() {
            let list = this.conversations;
            if (this.activeFilter === 'unread') list = list.filter(c => c.unread_count > 0);
            const q = (this.$store.comm.search || '').trim().toLowerCase();
            if (q) {
                list = list.filter(c =>
                    (c.other_user.name || '').toLowerCase().includes(q) ||
                    (c.other_user.role || '').toLowerCase().includes(q) ||
                    (c.other_user.center_name || '').toLowerCase().includes(q) ||
                    (c.last_message?.content || '').toLowerCase().includes(q)
                );
            }
            return list;
        },

        initials(name) {
            if (!name) return '?';
            const parts = name.trim().split(/\s+/).filter(Boolean);
            if (parts.length >= 2) return (parts[0].charAt(0) + parts[1].charAt(0)).toUpperCase();
            return parts[0].slice(0, 2).toUpperCase();
        },

        avatarClass(name) {
            const palette = [
                'bg-gradient-to-br from-brand-500 to-indigo-600',
                'bg-gradient-to-br from-sky-500 to-blue-600',
                'bg-gradient-to-br from-emerald-500 to-teal-600',
                'bg-gradient-to-br from-fuchsia-500 to-purple-600',
                'bg-gradient-to-br from-pink-500 to-rose-600',
                'bg-gradient-to-br from-amber-500 to-orange-600',
                'bg-gradient-to-br from-violet-500 to-purple-700',
                'bg-gradient-to-br from-cyan-500 to-sky-600',
            ];
            let h = 0;
            const s = name || '?';
            for (let i = 0; i < s.length; i++) h = (h * 31 + s.charCodeAt(i)) >>> 0;
            return palette[h % palette.length];
        },

        async init() {
            await this.loadConversations();
            if (this.activeConversationId) {
                await this.loadMessages(this.activeConversationId);
            }
            window.addEventListener('resize', () => { this.isMobile = window.innerWidth < 640; });
            this.pollingInterval = setInterval(async () => {
                await this.loadConversations(true);
                if (this.activeConversationId) {
                    await this.pollMessages();
                }
            }, 3000);
        },

        async loadConversations(silent = false) {
            if (!silent) this.loading = true;
            try {
                const res = await fetch('{{ route('messages.conversations') }}');
                const data = await res.json();
                this.conversations = data.conversations || [];
                this.totalUnread = data.total_unread || 0;
            } catch (e) {
                console.error(e);
            } finally {
                this.loading = false;
            }
        },

        async openConversation(id) {
            if (this.activeConversationId === id && this.currentMessages.length > 0) return;
            this.activeConversationId = id;
            await this.loadMessages(id);
        },

        async loadMessages(conversationId) {
            this.loadingMessages = true;
            this.currentMessages = [];
            try {
                const res = await fetch(`/messages/conversations/${conversationId}`);
                if (!res.ok) {
                    this.activeConversationId = null;
                    return;
                }
                const data = await res.json();
                this.currentMessages = data.messages || [];
                this.currentOther = data.other_user || null;
                this.totalUnread = data.total_unread ?? this.totalUnread;
                const conv = this.conversations.find(c => c.id === conversationId);
                if (conv) conv.unread_count = 0;
                this.$nextTick(() => this.scrollToBottom());
            } catch (e) {
                console.error(e);
            } finally {
                this.loadingMessages = false;
            }
        },

        async pollMessages() {
            if (!this.activeConversationId) return;
            try {
                const res = await fetch(`/messages/conversations/${this.activeConversationId}`);
                if (!res.ok) return;
                const data = await res.json();
                const newMessages = data.messages || [];
                if (newMessages.length > this.currentMessages.length) {
                    this.currentMessages = newMessages;
                    this.totalUnread = data.total_unread ?? this.totalUnread;
                    this.$nextTick(() => this.scrollToBottom());
                }
            } catch (e) {}
        },

        scrollToBottom() {
            const area = this.$refs.scrollArea;
            if (area) area.scrollTop = area.scrollHeight;
        },
    };
}
</script>
@endpush
@endsection
