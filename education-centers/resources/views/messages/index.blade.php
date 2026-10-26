@extends('layouts.app')

@section('title', __('Messagerie') . ' — ' . config('app.name'))
@section('main-class', 'overflow-hidden p-0')

@section('content')
<div class="flex overflow-hidden bg-slate-50 dark:bg-slate-950"
     style="height: calc(100vh - 4rem)"
     x-data="messagingApp()"
     x-init="init()"
     id="messaging-app">

    {{-- ═══════════════════════════════════════════════════════════════════════
         PANNEAU GAUCHE — LISTE DES CONVERSATIONS
    ══════════════════════════════════════════════════════════════════════════ --}}
    <div class="flex w-full flex-col border-e border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900 sm:w-80 md:w-96"
         :class="activeConversationId && isMobile ? 'hidden' : 'flex'">

        {{-- Header --}}
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-4 dark:border-slate-800">
            <div>
                <h1 class="text-base font-bold text-slate-900 dark:text-white">{{ __('💬 Messagerie') }}</h1>
                <p class="text-xs text-slate-400" x-text="totalUnread > 0 ? totalUnread + ' ' + @js(__('message(s) non lu(s)')) : @js(__('Aucun nouveau message'))"></p>
            </div>
            {{-- Bouton nouveau message --}}
            <button type="button"
                    id="btn-new-conversation"
                    @click="showContactsPanel = !showContactsPanel"
                    class="flex items-center gap-1.5 rounded-lg bg-[#0DAD98] px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-[#0b9b88] transition-colors">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                {{ __('Nouveau') }}
            </button>
        </div>

        {{-- Recherche --}}
        <div class="border-b border-slate-100 px-4 py-2 dark:border-slate-800">
            <div class="relative">
                <svg class="absolute start-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <input type="text"
                       x-model="searchQuery"
                       placeholder="{{ __('Rechercher une conversation…') }}"
                       class="w-full rounded-lg border border-slate-200 bg-slate-50 py-2 ps-9 pe-4 text-sm placeholder:text-slate-400 focus:border-[#0DAD98] focus:outline-none focus:ring-2 focus:ring-[#0DAD98]/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:placeholder:text-slate-500">
            </div>
        </div>

        {{-- Panneau nouveaux contacts (contacts disponibles) --}}
        <div x-show="showContactsPanel" x-transition class="border-b border-slate-100 bg-[#ECFDF5] dark:border-slate-800 dark:bg-[#0DAD98]/10">
            <div class="px-4 pt-3 pb-1">
                <p class="text-xs font-bold uppercase tracking-wider text-[#0DAD98]">{{ __('Démarrer une conversation') }}</p>
            </div>
            <div class="max-h-48 overflow-y-auto">
                @foreach($contacts as $contact)
                <button type="button"
                        @click="startDirect({{ $contact->id }})"
                        class="flex w-full items-center gap-3 px-4 py-2.5 text-start hover:bg-[#0DAD98]/10 transition-colors">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#0DAD98] text-xs font-bold text-white uppercase">
                        {{ substr($contact->name, 0, 1) }}
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-slate-900 dark:text-white">{{ $contact->name }}</p>
                        <p class="truncate text-xs text-slate-500">{{ $contact->isSuperAdmin() ? __('Super Admin') : ($contact->center?->name ?? __('Admin Centre')) }}</p>
                    </div>
                </button>
                @endforeach
                @if($contacts->isEmpty())
                <p class="px-4 py-3 text-xs text-slate-500">{{ __('Aucun contact disponible.') }}</p>
                @endif
            </div>
        </div>

        {{-- Liste des conversations --}}
        <div class="flex-1 overflow-y-auto">
            <template x-if="loading && conversations.length === 0">
                <div class="flex items-center justify-center py-16 text-slate-400">
                    <svg class="h-5 w-5 animate-spin me-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ __('Chargement…') }}
                </div>
            </template>

            <template x-if="!loading && filteredConversations.length === 0">
                <div class="flex flex-col items-center justify-center py-16 text-slate-400">
                    <svg class="h-10 w-10 mb-3 opacity-40" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a.75.75 0 0 1-.974-.948l.78-2.34A8.09 8.09 0 0 1 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
                    </svg>
                    <p class="text-sm font-medium">{{ __('Aucune conversation') }}</p>
                    <p class="text-xs mt-1">{{ __('Démarrez une nouvelle conversation ci-dessus') }}</p>
                </div>
            </template>

            <ul>
                <template x-for="conv in filteredConversations" :key="conv.id">
                    <li>
                        <button type="button"
                                @click="openConversation(conv.id)"
                                class="flex w-full items-center gap-3 px-4 py-3 text-start transition-colors border-b border-slate-50 dark:border-slate-800/60 hover:bg-slate-50 dark:hover:bg-slate-800/40"
                                :class="activeConversationId === conv.id ? 'bg-[#ECFDF5] dark:bg-[#0DAD98]/10 border-s-2 border-s-[#0DAD98]' : ''">
                            {{-- Avatar --}}
                            <div class="relative shrink-0">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-[#0DAD98] to-[#0b9b88] text-sm font-bold text-white uppercase"
                                     x-text="conv.other_user.name ? conv.other_user.name.charAt(0) : '?'">
                                </div>
                                <span x-show="conv.unread_count > 0"
                                      class="absolute -top-0.5 -end-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-[#0DAD98] text-[9px] font-bold text-white">
                                </span>
                            </div>
                            {{-- Info --}}
                            <div class="min-w-0 flex-1">
                                <div class="flex items-baseline justify-between gap-1">
                                    <p class="truncate text-sm font-semibold text-slate-900 dark:text-white"
                                       :class="conv.unread_count > 0 ? 'font-bold' : ''"
                                       x-text="conv.other_user.name || @js(__('Utilisateur'))"></p>
                                    <span class="shrink-0 text-[10px] text-slate-400"
                                          x-text="conv.last_message ? conv.last_message.time : ''"></span>
                                </div>
                                <div class="flex items-center justify-between gap-1 mt-0.5">
                                    <p class="truncate text-xs text-slate-500"
                                       :class="conv.unread_count > 0 ? 'font-semibold text-slate-700 dark:text-slate-300' : ''"
                                       x-text="conv.last_message ? (conv.last_message.is_mine ? @js(__('Vous: ')) : '') + conv.last_message.content : conv.other_user.center_name"></p>
                                    <span x-show="conv.unread_count > 0"
                                          x-text="conv.unread_count"
                                          class="shrink-0 flex h-5 min-w-5 items-center justify-center rounded-full bg-[#0DAD98] px-1 text-[10px] font-bold text-white">
                                    </span>
                                </div>
                                <p class="text-[10px] text-slate-400 mt-0.5" x-text="conv.other_user.center_name"></p>
                            </div>
                        </button>
                    </li>
                </template>
            </ul>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════════
         PANNEAU DROIT — FENÊTRE DE DISCUSSION
    ══════════════════════════════════════════════════════════════════════════ --}}
    <div class="flex flex-1 flex-col bg-[#F8FAFC] dark:bg-slate-950"
         :class="!activeConversationId && isMobile ? 'hidden' : 'flex'">

        {{-- Pas de conversation sélectionnée --}}
        <template x-if="!activeConversationId">
            <div class="flex h-full flex-col items-center justify-center text-center text-slate-400 px-8">
                <div class="mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800">
                    <svg class="h-10 w-10 text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a.75.75 0 0 1-.974-.948l.78-2.34A8.09 8.09 0 0 1 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
                    </svg>
                </div>
                <p class="text-base font-semibold text-slate-600 dark:text-slate-300">{{ __('Sélectionnez une conversation') }}</p>
                <p class="mt-2 text-sm text-slate-400">{{ __('Choisissez une conversation dans la liste ou démarrez-en une nouvelle.') }}</p>
            </div>
        </template>

        {{-- Conversation active --}}
        <template x-if="activeConversationId">
            <div class="flex h-full flex-col">

                {{-- Header de conversation --}}
                <div class="flex items-center gap-3 border-b border-slate-200 bg-white px-4 py-3 dark:border-slate-800 dark:bg-slate-900 shadow-sm">
                    {{-- Bouton retour mobile --}}
                    <button type="button"
                            @click="activeConversationId = null; currentMessages = []; currentOther = null"
                            class="rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 sm:hidden">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                        </svg>
                    </button>
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-[#0DAD98] to-[#0b9b88] text-sm font-bold text-white uppercase"
                         x-text="currentOther ? currentOther.name.charAt(0) : '?'">
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-bold text-slate-900 dark:text-white" x-text="currentOther ? currentOther.name : '…'"></p>
                        <p class="truncate text-xs text-slate-500" x-text="currentOther ? currentOther.role + ' · ' + currentOther.center_name : ''"></p>
                    </div>
                    {{-- Indicateur de polling --}}
                    <div class="flex items-center gap-1.5 rounded-full bg-green-50 px-2.5 py-1 text-[10px] font-semibold text-green-600 dark:bg-green-900/30 dark:text-green-400">
                        <span class="h-1.5 w-1.5 rounded-full bg-green-500 animate-pulse"></span>
                        {{ __('En ligne') }}
                    </div>
                </div>

                {{-- Zone de messages --}}
                <div class="flex-1 overflow-y-auto px-4 py-4 space-y-3" id="messages-scroll-area" x-ref="scrollArea">
                    <template x-if="loadingMessages">
                        <div class="flex items-center justify-center py-12 text-slate-400">
                            <svg class="h-5 w-5 animate-spin me-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ __('Chargement des messages…') }}
                        </div>
                    </template>

                    <template x-if="!loadingMessages && currentMessages.length === 0">
                        <div class="flex flex-col items-center justify-center py-12 text-slate-400">
                            <p class="text-sm">{{ __('Aucun message pour l\'instant.') }}</p>
                            <p class="text-xs mt-1">{{ __('Envoyez le premier message !') }}</p>
                        </div>
                    </template>

                    {{-- Messages groupés par date --}}
                    <template x-for="(msg, index) in currentMessages" :key="msg.id">
                        <div>
                            {{-- Séparateur de date si nouvelle journée --}}
                            <template x-if="index === 0 || currentMessages[index-1].date !== msg.date">
                                <div class="flex items-center my-4">
                                    <div class="flex-1 border-t border-slate-200 dark:border-slate-700"></div>
                                    <span class="mx-3 text-[11px] font-semibold text-slate-400 bg-[#F8FAFC] dark:bg-slate-950 px-2" x-text="msg.date"></span>
                                    <div class="flex-1 border-t border-slate-200 dark:border-slate-700"></div>
                                </div>
                            </template>

                            {{-- Bulle de message --}}
                            <div class="flex"
                                 :class="msg.is_mine ? 'justify-end' : 'justify-start'">
                                <div class="group max-w-[75%]">
                                    {{-- Contenu texte --}}
                                    <template x-if="msg.content">
                                        <div class="rounded-2xl px-4 py-2.5 text-sm shadow-xs"
                                             :class="msg.is_mine
                                                 ? 'bg-[#0DAD98] text-white rounded-tr-sm'
                                                 : 'bg-white text-slate-800 dark:bg-slate-800 dark:text-slate-100 rounded-tl-sm border border-slate-100 dark:border-slate-700'">
                                            <p class="leading-relaxed whitespace-pre-wrap" x-text="msg.content"></p>
                                            <p class="mt-1 text-end text-[10px]"
                                               :class="msg.is_mine ? 'text-white/70' : 'text-slate-400'"
                                               x-text="msg.time"></p>
                                        </div>
                                    </template>

                                    {{-- Pièces jointes --}}
                                    <template x-if="msg.attachments && msg.attachments.length > 0">
                                        <div>
                                            <template x-for="att in msg.attachments" :key="att.id">
                                                <div class="mt-1">
                                                    <template x-if="att.is_image">
                                                        <a :href="att.url" target="_blank"
                                                           class="block overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700 shadow-xs">
                                                            <img :src="att.url" :alt="att.name" class="max-h-48 w-auto rounded-xl object-cover" />
                                                            <p class="px-3 py-1 text-[10px] text-slate-400" x-text="att.size"></p>
                                                        </a>
                                                    </template>
                                                    <template x-if="!att.is_image">
                                                        <a :href="att.url"
                                                           class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm shadow-xs hover:bg-slate-50 transition-colors dark:border-slate-700 dark:bg-slate-800 dark:hover:bg-slate-750">
                                                            <svg class="h-8 w-8 shrink-0 text-[#0DAD98]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                                            </svg>
                                                            <div class="min-w-0">
                                                                <p class="truncate font-semibold text-slate-700 dark:text-slate-200" x-text="att.name"></p>
                                                                <p class="text-xs text-slate-400" x-text="att.size"></p>
                                                            </div>
                                                            <svg class="h-4 w-4 shrink-0 text-slate-400 ms-auto" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                                            </svg>
                                                        </a>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Barre de composition --}}
                <div class="border-t border-slate-200 bg-white px-4 py-3 dark:border-slate-800 dark:bg-slate-900">

                    {{-- Aperçu fichier sélectionné --}}
                    <div x-show="selectedFile" class="mb-2 flex items-center gap-2 rounded-lg bg-[#ECFDF5] px-3 py-2 text-sm text-[#0DAD98]">
                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                        </svg>
                        <span class="flex-1 truncate text-xs font-medium" x-text="selectedFile ? selectedFile.name : ''"></span>
                        <button type="button" @click="selectedFile = null; $refs.fileInput.value = ''" class="text-[#0DAD98]/70 hover:text-red-500 transition-colors">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form @submit.prevent="sendMessage()" class="flex items-end gap-2">
                        {{-- Bouton pièce jointe --}}
                        <input type="file"
                               x-ref="fileInput"
                               @change="selectedFile = $event.target.files[0]"
                               accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg,.webp"
                               class="hidden">
                        <button type="button"
                                @click="$refs.fileInput.click()"
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 text-slate-400 hover:border-[#0DAD98] hover:bg-[#ECFDF5] hover:text-[#0DAD98] transition-colors dark:border-slate-700 dark:hover:border-[#0DAD98] dark:hover:bg-[#0DAD98]/10">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" />
                            </svg>
                        </button>

                        {{-- Zone de texte --}}
                        <div class="relative flex-1">
                            <textarea x-ref="messageInput"
                                      x-model="messageText"
                                      @keydown.enter.prevent="if (!$event.shiftKey) sendMessage()"
                                      placeholder="{{ __('Écrivez votre message… (Entrée pour envoyer, Maj+Entrée pour saut de ligne)') }}"
                                      rows="1"
                                      class="w-full resize-none overflow-hidden rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm placeholder:text-slate-400 focus:border-[#0DAD98] focus:outline-none focus:ring-2 focus:ring-[#0DAD98]/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:placeholder:text-slate-500"
                                      style="min-height:42px; max-height:120px;">
                            </textarea>
                        </div>

                        {{-- Bouton envoyer --}}
                        <button type="submit"
                                :disabled="sending || (!messageText.trim() && !selectedFile)"
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#0DAD98] text-white shadow-sm hover:bg-[#0b9b88] disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                            <template x-if="!sending">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                                </svg>
                            </template>
                            <template x-if="sending">
                                <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            </template>
                        </button>
                    </form>
                </div>

            </div>
        </template>
    </div>
</div>

@push('scripts')
<script>
function messagingApp() {
    return {
        // State
        conversations: [],
        currentMessages: [],
        currentOther: null,
        activeConversationId: {{ $activeConversationId ? (int)$activeConversationId : 'null' }},
        loading: true,
        loadingMessages: false,
        sending: false,
        totalUnread: 0,
        searchQuery: '',
        showContactsPanel: false,
        messageText: '',
        selectedFile: null,
        pollingInterval: null,
        isMobile: window.innerWidth < 640,

        // CSRF token
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.content || '',

        // Localized strings
        translations: {
            accessDenied: @js(__('Accès refusé à cette conversation.')),
            cannotStart: @js(__('Impossible de démarrer la conversation.')),
            networkError: @js(__('Erreur réseau.')),
            sendError: @js(__('Erreur lors de l\'envoi.')),
            networkSendError: @js(__('Erreur réseau lors de l\'envoi.')),
            attachment: @js(__('📎 Fichier joint')),
        },

        get filteredConversations() {
            if (!this.searchQuery.trim()) return this.conversations;
            const q = this.searchQuery.toLowerCase();
            return this.conversations.filter(c =>
                c.other_user.name.toLowerCase().includes(q) ||
                (c.other_user.center_name || '').toLowerCase().includes(q) ||
                (c.last_message?.content || '').toLowerCase().includes(q)
            );
        },

        async init() {
            await this.loadConversations();
            if (this.activeConversationId) {
                await this.loadMessages(this.activeConversationId);
            }
            // Poll every 3 seconds for new messages and conversations
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
                console.error('Failed to load conversations', e);
            } finally {
                this.loading = false;
            }
        },

        async openConversation(id) {
            if (this.activeConversationId === id && this.currentMessages.length > 0) return;
            this.activeConversationId = id;
            this.showContactsPanel = false;
            await this.loadMessages(id);
        },

        async loadMessages(conversationId) {
            this.loadingMessages = true;
            this.currentMessages = [];
            try {
                const res = await fetch(`/messages/conversations/${conversationId}`);
                if (!res.ok) {
                    if (res.status === 403) {
                        alert(this.translations.accessDenied);
                        this.activeConversationId = null;
                        return;
                    }
                    throw new Error('HTTP ' + res.status);
                }
                const data = await res.json();
                this.currentMessages = data.messages || [];
                this.currentOther = data.other_user || null;
                this.totalUnread = data.total_unread ?? this.totalUnread;
                // Update unread in conversation list
                const conv = this.conversations.find(c => c.id === conversationId);
                if (conv) conv.unread_count = 0;
                this.$nextTick(() => this.scrollToBottom());
            } catch (e) {
                console.error('Failed to load messages', e);
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

        async startDirect(targetId) {
            try {
                const res = await fetch(`/messages/start-direct/${targetId}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    }
                });
                const data = await res.json();
                if (data.conversation_id) {
                    await this.loadConversations(true);
                    await this.openConversation(data.conversation_id);
                    this.showContactsPanel = false;
                } else {
                    alert(data.error || this.translations.cannotStart);
                }
            } catch (e) {
                console.error(e);
                alert(this.translations.networkError);
            }
        },

        async sendMessage() {
            if (this.sending) return;
            if (!this.messageText.trim() && !this.selectedFile) return;
            if (!this.activeConversationId) return;

            this.sending = true;
            try {
                const formData = new FormData();
                if (this.messageText.trim()) formData.append('content', this.messageText.trim());
                if (this.selectedFile) formData.append('file', this.selectedFile);
                formData.append('_token', this.csrfToken);

                const res = await fetch(`/messages/conversations/${this.activeConversationId}`, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    body: formData,
                });
                const data = await res.json();
                if (data.success) {
                    this.currentMessages.push(data.message);
                    this.messageText = '';
                    this.selectedFile = null;
                    if (this.$refs.fileInput) this.$refs.fileInput.value = '';
                    // Update conversation list last message
                    const conv = this.conversations.find(c => c.id === this.activeConversationId);
                    if (conv) {
                        conv.last_message = {
                            content: data.message.content || (data.message.attachments?.length ? this.translations.attachment : ''),
                            time: data.message.time,
                            is_mine: true,
                        };
                        // Move to top
                        this.conversations = [conv, ...this.conversations.filter(c => c.id !== this.activeConversationId)];
                    }
                    this.$nextTick(() => this.scrollToBottom());
                } else {
                    alert(data.error || this.translations.sendError);
                }
            } catch (e) {
                console.error(e);
                alert(this.translations.networkSendError);
            } finally {
                this.sending = false;
            }
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
