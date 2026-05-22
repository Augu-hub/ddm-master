<template>
    <VerticalLayout>
        <div class="mp-shell">

            <!-- ══ HEADER ══ -->
            <header class="mp-header" :style="`--mc:${mc};--mcl:${mc}18;--mcm:${mc}35`">
                <div class="mph-row">
                    <a :href="props.missionsUrl || `${baseUrl}/m/audit.core/auditor/missions`" class="mph-back">
                        <i class="ti ti-arrow-left"></i>
                    </a>

                    <div class="mph-info">
                        <div class="mph-chips">
                            <code class="mph-code" :style="`color:${mc};background:${mc}15;border-color:${mc}30`">
                                {{ mission.code_mission }}
                            </code>
                            <span class="mph-status" :class="`st-${mission.status}`">{{ stLbl(mission.status) }}</span>
                            <span v-if="auditor.role" class="mph-myrole" :class="`rb-${auditor.role}`">
                                <i class="ti ti-shield-half"></i> {{ auditor.role }}
                            </span>
                            <span v-if="mission.audit_type_label" class="mph-type" :style="`color:${mc}`">
                                <i class="ti ti-tag"></i> {{ mission.audit_type_label }}
                            </span>
                        </div>
                        <h1 class="mph-title">{{ mission.libelle }}</h1>
                        <div class="mph-meta">
                            <span v-if="mission.date_debut_fr"><i class="ti ti-calendar"></i>{{ mission.date_debut_fr }} — {{ mission.date_fin_fr }}</span>
                            <span v-if="mission.duree_totale"><i class="ti ti-clock"></i>{{ mission.duree_totale }} jours</span>
                            <span v-if="mission.lieux"><i class="ti ti-map-pin"></i>{{ mission.lieux }}</span>
                        </div>
                    </div>

                    <!-- Équipe -->
                    <div v-if="equipe.length" class="mph-team">
                        <div class="mph-avs">
                            <div v-for="m in equipe.slice(0,5)" :key="m.auditeur_id"
                                class="mph-av" :class="`av-${m.role}`"
                                :title="`${m.last_name} ${m.first_name} · ${m.role_libelle}`">
                                {{ ini(m.last_name, m.first_name) }}
                                <span v-if="m.is_me" class="mph-av-me"></span>
                            </div>
                            <div v-if="equipe.length > 5" class="mph-av mph-av-more">+{{ equipe.length - 5 }}</div>
                        </div>
                        <span class="mph-team-cnt">{{ equipe.length }} membres</span>
                    </div>

                    <!-- Anneau progression -->
                    <div class="mph-ring">
                        <svg viewBox="0 0 36 36">
                            <circle cx="18" cy="18" r="15.5" fill="none" stroke="#e2e8f0" stroke-width="2.6"/>
                            <circle cx="18" cy="18" r="15.5" fill="none"
                                :stroke="mc" stroke-width="2.6" stroke-linecap="round"
                                :stroke-dasharray="`${globalPct} ${100 - globalPct}`"
                                stroke-dashoffset="25"
                                style="transition:stroke-dasharray .6s ease"/>
                        </svg>
                        <div class="mph-ring-c">
                            <span class="mph-ring-pct" :style="`color:${mc}`">{{ globalPct }}%</span>
                            <span class="mph-ring-sub">{{ doneCount }}/{{ totalCount }}</span>
                        </div>
                    </div>
                </div>

                <!-- Barre types -->
                <div class="mph-typebar">
                    <button v-for="grp in phasesByType" :key="grp.phase_type"
                        class="mph-typebtn"
                        :class="{ active: filterType === grp.phase_type }"
                        :style="filterType === grp.phase_type ? `border-bottom-color:${grp.color||mc};color:${grp.color||mc}` : ''"
                        @click="filterType = filterType === grp.phase_type ? null : grp.phase_type">
                        <span class="mtb-dot" :style="`background:${grp.color||mc}`"></span>
                        {{ grp.label }}
                        <span class="mtb-cnt">{{ (grp.phases||[]).length }}</span>
                        <span class="mtb-done" :style="`color:${grp.color||mc}`">{{ grp.stats?.completed ?? 0 }} ✓</span>
                        <span v-if="groupUnreadCount(grp.phase_type) > 0" class="mtb-unread">
                            {{ groupUnreadCount(grp.phase_type) }}
                        </span>
                    </button>
                </div>
            </header>

            <!-- ══ BODY ══ -->
            <div class="mp-body">

                <!-- Sidebar -->
                <aside class="mp-sidebar">
                    <div class="mps-block">
                        <div class="mps-label"><i class="ti ti-buildings"></i> Entités <span class="mps-badge">{{ entities.length }}</span></div>
                        <div class="mps-list">
                            <button class="mps-item" :class="{ on: activeEntity === null }" @click="activeEntity = null">
                                <span class="mps-dot" style="background:#94a3b8"></span>
                                Toutes les entités
                            </button>
                            <button v-for="e in entities" :key="e.entity_id"
                                class="mps-item" :class="{ on: activeEntity === e.entity_id }"
                                @click="activeEntity = e.entity_id">
                                <span class="mps-dot" :style="`background:${mc}`"></span>
                                <span class="mps-item-txt">
                                    <span class="mps-item-name">{{ e.entity_name }}</span>
                                    <span v-if="e.date_debut_fr" class="mps-item-date">{{ e.date_debut_fr }} › {{ e.date_fin_fr }}</span>
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Vue mode -->
                    <div class="mps-block mps-view-block">
                        <div class="mps-label"><i class="ti ti-layout-list"></i> Affichage</div>
                        <div class="mps-view-btns">
                            <button class="mps-vbtn" :class="{on: viewMode==='hierarchy'}" @click="viewMode='hierarchy'">
                                <i class="ti ti-sitemap"></i> Hiérarchie
                            </button>
                            <button class="mps-vbtn" :class="{on: viewMode==='timeline'}" @click="viewMode='timeline'">
                                <i class="ti ti-calendar-event"></i> Timeline
                            </button>
                        </div>
                    </div>

                    <div class="mps-block mps-team-block">
                        <div class="mps-label"><i class="ti ti-users"></i> Équipe <span class="mps-badge">{{ equipe.length }}</span></div>
                        <div class="mps-team">
                            <div v-for="m in equipe" :key="m.auditeur_id"
                                class="mps-member" :class="{ 'mps-me': m.is_me }">
                                <div class="mps-av" :class="`av-${m.role}`">{{ ini(m.last_name, m.first_name) }}</div>
                                <div class="mps-minfo">
                                    <span class="mps-mname">{{ m.last_name }} {{ m.first_name }} <span v-if="m.is_me" class="mps-me-tag">moi</span></span>
                                    <span class="mps-mrole" :class="`rb-${m.role}`">{{ m.role }} · {{ m.role_libelle }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </aside>

                <!-- Main -->
                <main class="mp-main" ref="mainEl">
                    <div v-if="!filteredGroups.length && viewMode === 'hierarchy'" class="mp-empty">
                        <i class="ti ti-calendar-off"></i>
                        <p>Aucune phase à afficher</p>
                    </div>
                    <div v-if="!timelineGroups.length && viewMode === 'timeline'" class="mp-empty">
                        <i class="ti ti-calendar-off"></i>
                        <p>Aucune phase à afficher</p>
                    </div>

                    <!-- ═══ VUE HIÉRARCHIE ═══ -->
                    <template v-if="viewMode === 'hierarchy'">
                        <section v-for="grp in filteredGroups" :key="grp.phase_type" class="type-section">
                            <!-- En-tête de type sticky -->
                            <div class="type-sticky">
                                <div class="type-sticky-inner">
                                    <div class="type-pill" :style="`background:${grp.color}15;border-color:${grp.color}30`">
                                        <span class="type-pill-dot" :style="`background:${grp.color}`"></span>
                                        <span class="type-pill-label" :style="`color:${grp.color}`">{{ grp.label }}</span>
                                    </div>
                                    <div class="type-stats">
                                        <span class="ts-item ts-total">{{ grp.stats.total }} phase{{ grp.stats.total > 1 ? 's' : '' }}</span>
                                        <span v-if="grp.stats.completed" class="ts-item ts-done"><i class="ti ti-circle-check"></i>{{ grp.stats.completed }} terminée{{ grp.stats.completed > 1 ? 's' : '' }}</span>
                                        <span v-if="grp.stats.in_progress" class="ts-item ts-ip"><i class="ti ti-loader-2"></i>{{ grp.stats.in_progress }} en cours</span>
                                        <span v-if="grp.stats.pending" class="ts-item ts-pend"><i class="ti ti-clock"></i>{{ grp.stats.pending }} à faire</span>
                                    </div>
                                    <div class="type-pbar">
                                        <div class="type-pbar-fill" :style="`width:${grp.stats.total ? Math.round(grp.stats.completed/grp.stats.total*100) : 0}%;background:${grp.color}`"></div>
                                    </div>
                                    <span class="type-pct" :style="`color:${grp.color}`">
                                        {{ grp.stats.total ? Math.round(grp.stats.completed/grp.stats.total*100) : 0 }}%
                                    </span>
                                </div>
                            </div>

                            <!-- Phases hiérarchiques -->
                            <div class="hier-container">
                                <div v-for="root in grp.rootPhases" :key="root.assignment_id" class="hier-root">
                                    <!-- Phase parente -->
                                    <PhaseCard
                                        :ph="root"
                                        :mc="mc"
                                        :canManage="canManage"
                                        :auditorId="auditor.id"
                                        :isParent="(grp.childrenMap[root.assignment_id]||[]).length > 0"
                                        :childCount="(grp.childrenMap[root.assignment_id]||[]).length"
                                        :expanded="expandedParents.has(root.assignment_id)"
                                        :unreadCount="unreadCount(root.assignment_id)"
                                        :msgCount="msgCount(root.assignment_id)"
                                        :markings="phMks(root.assignment_id)"
                                        :chatPreviewMsgs="chatPreview(root.assignment_id)"
                                        @toggleExpand="toggleExpand(root.assignment_id)"
                                        @start="startPhase(root)"
                                        @openChat="openChat(root)"
                                        @openNote="openNote(root)"
                                        @openValidate="openValidate(root)"
                                        @toggleDisabled="toggleDisabled(root)"
                                    />

                                    <!-- Sous-phases -->
                                    <transition name="sub-expand">
                                        <div v-if="(grp.childrenMap[root.assignment_id]||[]).length > 0 && expandedParents.has(root.assignment_id)"
                                            class="hier-children">
                                            <div class="hier-children-line" :style="`border-color:${grp.color}40`"></div>
                                            <div class="hier-children-cards">
                                                <div v-for="child in grp.childrenMap[root.assignment_id]" :key="child.assignment_id"
                                                    class="hier-child-wrap">
                                                    <div class="hier-child-connector" :style="`border-color:${grp.color}40`">
                                                        <div class="hcc-dot" :style="`background:${grp.color}`"></div>
                                                    </div>
                                                    <PhaseCard
                                                        :ph="child"
                                                        :mc="mc"
                                                        :canManage="canManage"
                                                        :auditorId="auditor.id"
                                                        :isParent="false"
                                                        :isChild="true"
                                                        :childCount="0"
                                                        :unreadCount="unreadCount(child.assignment_id)"
                                                        :msgCount="msgCount(child.assignment_id)"
                                                        :markings="phMks(child.assignment_id)"
                                                        :chatPreviewMsgs="chatPreview(child.assignment_id)"
                                                        @start="startPhase(child)"
                                                        @openChat="openChat(child)"
                                                        @openNote="openNote(child)"
                                                        @openValidate="openValidate(child)"
                                                        @toggleDisabled="toggleDisabled(child)"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    </transition>
                                </div>
                            </div>
                        </section>
                    </template>

                    <!-- ═══ VUE TIMELINE (par mois) ═══ -->
                    <template v-else>
                        <section v-for="tg in timelineGroups" :key="tg.key" class="tg-section">
                            <div class="tg-sticky">
                                <div class="tg-sticky-inner">
                                    <div class="tg-date-pill">
                                        <i class="ti ti-calendar-event"></i>
                                        <span class="tgd-month">{{ tg.monthLabel }}</span>
                                        <span class="tgd-year">{{ tg.year }}</span>
                                    </div>
                                    <div class="tg-mission-recall">
                                        <span class="tgmr-code" :style="`color:${mc}`">{{ mission.code_mission }}</span>
                                        <span class="tgmr-sep">·</span>
                                        <span class="tgmr-name">{{ mission.libelle }}</span>
                                    </div>
                                    <div class="tg-right">
                                        <span class="tgr-cnt">{{ tg.phases.length }} phase{{ tg.phases.length > 1 ? 's' : '' }}</span>
                                        <span v-if="activeEntity !== null" class="tgr-ent"><i class="ti ti-building"></i> {{ entityName(activeEntity) }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="tg-phases">
                                <div v-for="ph in tg.phases" :key="ph.assignment_id"
                                    class="ph-row" :class="{'ph-row-done': ph.phase_status==='completed','ph-row-ip': ph.phase_status==='in_progress','ph-row-disabled': ph.is_disabled}">
                                    <div class="ph-tl">
                                        <div class="ph-tl-dot" :style="`background:${ph._grpColor};box-shadow:0 0 0 4px ${ph._grpColor}20`"></div>
                                        <div class="ph-tl-line"></div>
                                    </div>
                                    <PhaseCard
                                        :ph="ph" :mc="mc" :canManage="canManage"
                                        :auditorId="auditor.id"
                                        :isParent="false" :childCount="0"
                                        :unreadCount="unreadCount(ph.assignment_id)"
                                        :msgCount="msgCount(ph.assignment_id)"
                                        :markings="phMks(ph.assignment_id)"
                                        :chatPreviewMsgs="chatPreview(ph.assignment_id)"
                                        @start="startPhase(ph)"
                                        @openChat="openChat(ph)"
                                        @openNote="openNote(ph)"
                                        @openValidate="openValidate(ph)"
                                        @toggleDisabled="toggleDisabled(ph)"
                                    />
                                </div>
                            </div>
                        </section>
                    </template>
                </main>
            </div>
        </div>

        <!-- ══ PANEL CHAT ══ -->
        <Teleport to="body">
            <transition name="slide-right">
                <div v-if="chatPanel.show" class="chat-panel">
                    <div class="chat-hd">
                        <div class="chat-hd-info">
                            <i class="ti ti-message-circle" :style="`color:${mc}`"></i>
                            <div>
                                <span class="chat-hd-label">{{ chatPanel.label }}</span>
                                <span v-if="chatPanel.formCode" class="chat-hd-form">{{ chatPanel.formCode }}</span>
                            </div>
                        </div>
                        <span v-if="chatPanel.messages.length" class="chat-hd-count">{{ chatPanel.messages.length }} message(s)</span>
                        <button class="chat-close-btn" @click="closeChatPanel"><i class="ti ti-x"></i></button>
                    </div>
                    <div class="chat-members">
                        <div class="chat-members-label"><i class="ti ti-users"></i> Participants</div>
                        <div class="chat-members-avs">
                            <div v-for="m in equipe" :key="m.auditeur_id"
                                class="chat-mbr-av" :class="`av-${m.role}`"
                                :title="`${m.last_name} ${m.first_name} · ${m.role}`">
                                {{ ini(m.last_name, m.first_name) }}
                                <span v-if="m.is_me" class="chat-mbr-me"></span>
                            </div>
                        </div>
                    </div>
                    <div class="chat-msgs" ref="chatMsgEl">
                        <div v-if="!chatPanel.messages.length" class="chat-empty">
                            <i class="ti ti-messages"></i>
                            <p>Aucun message pour cette phase.</p>
                            <span>Soyez le premier à écrire !</span>
                        </div>
                        <template v-for="(dayGroup, dayKey) in chatByDay" :key="dayKey">
                            <div class="chat-day-sep"><span>{{ dayKey }}</span></div>
                            <div v-for="msg in dayGroup" :key="msg.id"
                                class="chat-msg" :class="[`chat-msg-${msg.type}`,`chat-pri-${msg.priority}`,{ 'chat-mine': msg.is_mine, 'chat-unread': !msg.is_read && !msg.is_mine }]">
                                <div class="cmsg-av" :class="`av-${msg.author_role}`">{{ msg.author_initials }}</div>
                                <div class="cmsg-body">
                                    <div class="cmsg-meta">
                                        <span class="cmsg-who" :class="`rb-${msg.author_role}`">{{ msg.author_name }}</span>
                                        <span class="cmsg-role">{{ msg.author_role }}</span>
                                        <span v-if="msg.type !== 'message'" class="cmsg-type" :class="`ctype-${msg.type}`">{{ chatTypeLbl(msg.type) }}</span>
                                        <span v-if="msg.priority !== 'normal'" class="cmsg-pri" :class="`cpri-${msg.priority}`">{{ msg.priority }}</span>
                                        <span class="cmsg-date">{{ msg.created_at_fr }}</span>
                                        <span v-if="!msg.is_read && !msg.is_mine" class="cmsg-new">Nouveau</span>
                                    </div>
                                    <p class="cmsg-txt">{{ msg.content }}</p>
                                    <div class="cmsg-actions">
                                        <button v-if="!msg.is_mine" class="cmsg-reply" @click="setReply(msg)"><i class="ti ti-corner-down-right"></i> Répondre</button>
                                        <span v-if="msg.parent_id" class="cmsg-thread"><i class="ti ti-corner-down-right"></i> En réponse à #{{ msg.parent_id }}</span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                    <div class="chat-compose">
                        <div v-if="chatPanel.replyTo" class="chat-reply-preview">
                            <i class="ti ti-corner-down-right"></i>
                            <span>Réponse à <strong>{{ chatPanel.replyTo.author_name }}</strong> : {{ chatPanel.replyTo.content.slice(0,60) }}{{ chatPanel.replyTo.content.length > 60 ? '…' : '' }}</span>
                            <button @click="chatPanel.replyTo = null"><i class="ti ti-x"></i></button>
                        </div>
                        <div class="chat-opts">
                            <select v-model="chatPanel.type" class="chat-select">
                                <option value="message">Message</option>
                                <option v-if="canManage" value="instruction">Instruction</option>
                                <option v-if="canManage" value="correction">Correction</option>
                                <option value="info">Info</option>
                            </select>
                            <div class="chat-prios">
                                <button v-for="p in PRIOS" :key="p.v"
                                    class="chat-prio-btn" :class="[{active: chatPanel.priority === p.v}, `cpb-${p.v}`]"
                                    @click="chatPanel.priority = p.v">
                                    <i :class="p.icon"></i> {{ p.l }}
                                </button>
                            </div>
                        </div>
                        <div class="chat-input-row">
                            <textarea v-model="chatPanel.draft" class="chat-ta" rows="2"
                                placeholder="Écrivez votre message… (Ctrl+Entrée pour envoyer)"
                                @keydown.ctrl.enter="sendChatMsg"></textarea>
                            <button class="chat-send" :disabled="!chatPanel.draft.trim() || chatPanel.sending" :style="`background:${mc}`" @click="sendChatMsg">
                                <i v-if="chatPanel.sending" class="ti ti-loader-2 spin"></i>
                                <i v-else class="ti ti-send"></i>
                            </button>
                        </div>
                        <div class="chat-hint">Ctrl+Entrée pour envoyer · Visible par tous les membres de la mission</div>
                    </div>
                </div>
            </transition>
            <div v-if="chatPanel.show" class="chat-overlay" @click="closeChatPanel"></div>
        </Teleport>

        <!-- ══ MODAL NOTE ══ -->
        <Teleport to="body">
            <transition name="mfade">
                <div v-if="noteModal.show" class="m-bg" @click.self="closeNote">
                    <div class="m-box">
                        <div class="m-hd">
                            <i class="ti ti-notes"></i> Note personnelle
                            <span class="m-hd-sub">{{ noteModal.label }}</span>
                            <button class="m-x" @click="closeNote"><i class="ti ti-x"></i></button>
                        </div>
                        <div class="m-bd">
                            <textarea v-model="noteModal.draft" class="m-ta" rows="5" placeholder="Votre note interne…"></textarea>
                        </div>
                        <div class="m-ft">
                            <button class="mbtn mbtn-gray" @click="closeNote">Annuler</button>
                            <button class="mbtn mbtn-primary" @click="saveNote"><i class="ti ti-check"></i> Enregistrer</button>
                        </div>
                    </div>
                </div>
            </transition>
        </Teleport>

        <!-- ══ MODAL VALIDATION ══ -->
        <Teleport to="body">
            <transition name="mfade">
                <div v-if="validateModal.show" class="m-bg" @click.self="closeValidate">
                    <div class="m-box m-box-lg">
                        <div class="m-hd m-hd-val">
                            <i class="ti ti-circle-check" style="color:#16a34a"></i>
                            Validation du formulaire
                            <button class="m-x" @click="closeValidate"><i class="ti ti-x"></i></button>
                        </div>
                        <div class="m-bd">
                            <div class="val-info">
                                <i class="ti ti-list-check" :style="`color:${mc}`"></i>
                                <strong>{{ validateModal.label }}</strong>
                                <span v-if="validateModal.formCode" class="val-form-code">{{ validateModal.formCode }}</span>
                            </div>
                            <div class="val-warn">
                                <i class="ti ti-alert-triangle"></i>
                                <span>Une fois validé, <strong>personne ne pourra plus modifier ce formulaire</strong>.</span>
                            </div>
                            <div class="val-choice">
                                <button class="val-btn val-reject" :class="{ active: validateModal.choice === 'reject' }" @click="validateModal.choice = 'reject'"><i class="ti ti-x"></i> Rejeter</button>
                                <button class="val-btn val-ok" :class="{ active: validateModal.choice === 'validate' }" @click="validateModal.choice = 'validate'"><i class="ti ti-check"></i> Valider définitivement</button>
                            </div>
                            <div class="val-note-wrap">
                                <label class="val-note-lbl">{{ validateModal.choice === 'reject' ? 'Motif du rejet *' : 'Note (optionnel)' }}</label>
                                <textarea v-model="validateModal.note" class="m-ta" rows="3"
                                    :placeholder="validateModal.choice === 'reject' ? 'Expliquer la raison…' : 'Commentaire optionnel…'"></textarea>
                            </div>
                        </div>
                        <div class="m-ft">
                            <button class="mbtn mbtn-gray" @click="closeValidate">Annuler</button>
                            <button class="mbtn"
                                :class="validateModal.choice === 'reject' ? 'mbtn-danger' : 'mbtn-success'"
                                :disabled="validateModal.choice === 'reject' && !validateModal.note.trim()"
                                @click="submitValidation">
                                <i :class="validateModal.choice === 'reject' ? 'ti ti-x' : 'ti ti-check'"></i>
                                {{ validateModal.choice === 'reject' ? 'Confirmer le rejet' : 'Confirmer la validation' }}
                            </button>
                        </div>
                    </div>
                </div>
            </transition>
        </Teleport>

        <!-- ══ TOAST ══ -->
        <Teleport to="body">
            <transition name="toast">
                <div v-if="toast.show" class="mp-toast" :class="`t-${toast.type}`">
                    <i :class="toast.type==='success'?'ti ti-circle-check':toast.type==='warning'?'ti ti-alert-triangle':'ti ti-circle-x'"></i>
                    {{ toast.msg }}
                    <button @click="toast.show=false"><i class="ti ti-x"></i></button>
                </div>
            </transition>
        </Teleport>
    </VerticalLayout>
</template>

<script setup lang="ts">
import { computed, reactive, ref, nextTick } from 'vue';
import { usePage } from '@inertiajs/vue3';
import PhaseCard from './PhaseCard.vue';  // ← import explicite, résout le warning Vue

const $page   = usePage();
const baseUrl = computed<string>(() =>
    ($page.props as any)?.ziggy?.url ?? ($page.props as any)?.appUrl ?? window.location.origin
);

const props = defineProps({
    mission:      { type: Object,  default: () => ({}) },
    phasesByType: { type: Array as () => any[], default: () => [] },
    entities:     { type: Array as () => any[], default: () => [] },
    equipe:       { type: Array as () => any[], default: () => [] },
    markingsData: { type: Object,  default: () => ({}) },
    auditor:      { type: Object,  default: () => ({}) },
    chatMessages: { type: Object,  default: () => ({}) },
    chatBaseUrl:  { type: String,  default: '' },
    missionsUrl:  { type: String,  default: '' },
});

// ── Couleur mission ───────────────────────────────────────────────────────────
const mc = computed<string>(() => {
    const c = (props.mission as any)?.audit_color;
    if (c && c !== '#000000' && c !== '#000' && c !== 'null') return c;
    const fg = props.phasesByType[0];
    return fg?.color && fg.color !== '#000000' ? fg.color : '#2563eb';
});

// ── États ─────────────────────────────────────────────────────────────────────
const activeEntity      = ref<number|null>(null);
const filterType        = ref<string|null>(null);
const viewMode          = ref<'hierarchy'|'timeline'>('hierarchy');
const localNotes        = reactive<Record<number,string>>({});
const mainEl            = ref<HTMLElement|null>(null);
const chatMsgEl         = ref<HTMLElement|null>(null);
const expandedParents   = reactive<Set<number>>(new Set());
const localChatMessages = reactive<Record<number, any[]>>({});

const noteModal     = ref({ show:false, id:0, label:'', draft:'' });
const validateModal = ref({ show:false, id:0, label:'', formCode:'', choice:'validate', note:'' });
const chatPanel     = ref({
    show:false, assignmentId:0, label:'', formCode:'', phaseType:'',
    messages:[] as any[], draft:'', type:'message', priority:'normal',
    replyTo: null as any, sending: false,
});
const toast = ref({ show:false, type:'success', msg:'' });

const PRIOS = [
    { v:'normal',   l:'Normal',   icon:'ti ti-info-circle' },
    { v:'urgent',   l:'Urgent',   icon:'ti ti-alert-triangle' },
    { v:'bloquant', l:'Bloquant', icon:'ti ti-alert-octagon' },
];

const canManage = computed(() => ['DM','CM'].includes((props.auditor as any)?.role ?? ''));

// ── Toutes les phases aplaties ────────────────────────────────────────────────
const allPhases = computed<any[]>(() =>
    props.phasesByType.flatMap(grp =>
        (grp.phases ?? []).map((ph: any) => ({
            ...ph,
            _grpColor: grp.color || mc.value,
            _grpLabel: grp.label,
            _grpType:  grp.phase_type,
        }))
    )
);

const totalCount = computed(() => allPhases.value.length);
const doneCount  = computed(() => allPhases.value.filter(p => p.phase_status === 'completed').length);
const globalPct  = computed(() => {
    if (!totalCount.value) return 0;
    return Math.round(allPhases.value.reduce((s,p) => s + (p.progression ?? 0), 0) / totalCount.value);
});

// ── Phases filtrées ───────────────────────────────────────────────────────────
const filteredAllPhases = computed<any[]>(() =>
    allPhases.value.filter(ph => {
        if (activeEntity.value !== null && ph.entity_id && ph.entity_id !== activeEntity.value) return false;
        if (filterType.value && ph._grpType !== filterType.value) return false;
        return true;
    })
);

// ── Groupes hiérarchiques ─────────────────────────────────────────────────────
const filteredGroups = computed(() => {
    const byType = new Map<string, any[]>();
    for (const ph of filteredAllPhases.value) {
        if (!byType.has(ph._grpType)) byType.set(ph._grpType, []);
        byType.get(ph._grpType)!.push(ph);
    }

    const result: any[] = [];
    for (const grp of props.phasesByType) {
        const pt = grp.phase_type;
        if (filterType.value && pt !== filterType.value) continue;
        const phases = byType.get(pt) ?? [];
        if (!phases.length) continue;

        // Tri : weight puis planned_start
        const sortFn = (a: any, b: any) => {
            const wa = a.weight ?? 999, wb = b.weight ?? 999;
            if (wa !== wb) return wa - wb;
            const da = a.planned_start ? new Date(a.planned_start).getTime() : Infinity;
            const db = b.planned_start ? new Date(b.planned_start).getTime() : Infinity;
            return da - db;
        };

        // Séparer racines et enfants
        // Racine = parent_id null/0 OU level === 0
        const rootPhases: any[] = [];
        const childrenMap: Record<number, any[]> = {};

        for (const ph of phases) {
            const isRoot = !ph.parent_id || ph.parent_id === 0 || ph.level === 0;
            if (isRoot) {
                rootPhases.push(ph);
            } else {
                // Résoudre le parent : chercher la phase dont mission_phase_id === ph.parent_id
                const parentPh = phases.find((p: any) => p.mission_phase_id === ph.parent_id);
                const parentId = parentPh?.assignment_id ?? null;
                if (parentId !== null) {
                    if (!childrenMap[parentId]) childrenMap[parentId] = [];
                    childrenMap[parentId].push(ph);
                } else {
                    // Parent absent du filtre → traiter comme racine
                    rootPhases.push(ph);
                }
            }
        }

        rootPhases.sort(sortFn);
        for (const key of Object.keys(childrenMap)) {
            childrenMap[parseInt(key)].sort(sortFn);
        }

        // Déplier toutes les racines par défaut
        for (const ph of rootPhases) {
            if (!expandedParents.has(ph.assignment_id) && (childrenMap[ph.assignment_id]?.length ?? 0) > 0) {
                expandedParents.add(ph.assignment_id);
            }
        }

        result.push({
            phase_type:  pt,
            label:       grp.label,
            color:       grp.color || mc.value,
            rootPhases,
            childrenMap,
            stats: grp.stats ?? {
                total:       phases.length,
                completed:   phases.filter((p: any) => p.phase_status === 'completed').length,
                in_progress: phases.filter((p: any) => p.phase_status === 'in_progress').length,
                pending:     phases.filter((p: any) => p.phase_status === 'pending').length,
                skipped:     phases.filter((p: any) => p.phase_status === 'skipped').length,
            },
        });
    }
    return result;
});

// ── Vue Timeline (par mois) ───────────────────────────────────────────────────
const MONTHS_FR = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];

const timelineGroups = computed(() => {
    const sorted = [...filteredAllPhases.value].sort((a, b) => {
        const da = a.planned_start ? new Date(a.planned_start).getTime() : Infinity;
        const db = b.planned_start ? new Date(b.planned_start).getTime() : Infinity;
        return da - db;
    });
    const map = new Map<string, any>();
    for (const ph of sorted) {
        let key: string, monthLabel: string, year: string;
        if (ph.planned_start) {
            const d = new Date(ph.planned_start);
            key = `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}`;
            monthLabel = MONTHS_FR[d.getMonth()];
            year = String(d.getFullYear());
        } else { key='sans-date'; monthLabel='Sans date'; year=''; }
        if (!map.has(key)) map.set(key, { key, monthLabel, year, phases: [] });
        map.get(key)!.phases.push(ph);
    }
    return [...map.values()];
});

// ── Toggle expand ─────────────────────────────────────────────────────────────
function toggleExpand(assignmentId: number) {
    if (expandedParents.has(assignmentId)) expandedParents.delete(assignmentId);
    else expandedParents.add(assignmentId);
}

// ── Chat messages ─────────────────────────────────────────────────────────────
function getChatMessages(id: number): any[] {
    if (localChatMessages[id] !== undefined) return localChatMessages[id];
    return (props.chatMessages as any)[id] ?? [];
}
function chatPreview(id: number): any[] { return getChatMessages(id).slice(-2); }
function msgCount(id: number): number   { return getChatMessages(id).length; }
function unreadCount(id: number): number { return getChatMessages(id).filter((m:any) => !m.is_read && !m.is_mine).length; }
function groupUnreadCount(phaseType: string): number {
    const group = props.phasesByType.find(g => g.phase_type === phaseType);
    if (!group) return 0;
    return (group.phases ?? []).reduce((sum:number, ph:any) => sum + unreadCount(ph.assignment_id), 0);
}
const chatByDay = computed<Record<string, any[]>>(() => {
    const groups: Record<string, any[]> = {};
    for (const msg of chatPanel.value.messages) {
        const datePart = (msg.created_at_fr ?? '').split(' ')[0] || 'Sans date';
        if (!groups[datePart]) groups[datePart] = [];
        groups[datePart].push(msg);
    }
    return groups;
});

// ── Actions ───────────────────────────────────────────────────────────────────
async function startPhase(ph: any) {
    if (!confirm(`Démarrer la phase "${ph.label}" ?`)) return;
    if (!ph.start_url) { showToast('URL de démarrage manquante.', 'error'); return; }
    try {
        const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '';
        const res  = await fetch(ph.start_url, { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf,Accept:'application/json'} });
        const json = await res.json();
        if (!res.ok) throw new Error(json?.message ?? 'Erreur');
        const dest = ph.form_url || json.form_url;
        if (dest) window.location.href = dest;
        else { ph.phase_status='in_progress'; ph.actual_start_fr=new Date().toLocaleDateString('fr-FR'); showToast('Phase démarrée.','success'); }
    } catch (e:any) { showToast('Erreur : '+e.message,'error'); }
}

function toggleDisabled(ph: any) {
    ph.is_disabled = !ph.is_disabled;
    showToast(ph.is_disabled ? 'Phase désactivée.' : 'Phase activée.', ph.is_disabled ? 'warning' : 'success');
}

function openChat(ph: any) {
    const messages = getChatMessages(ph.assignment_id);
    if (localChatMessages[ph.assignment_id] === undefined) localChatMessages[ph.assignment_id] = [...messages];
    localChatMessages[ph.assignment_id].forEach((m:any) => { if (!m.is_mine) m.is_read = true; });
    chatPanel.value = {
        show:true, assignmentId:ph.assignment_id, label:ph.label, formCode:ph.form_code??'',
        phaseType:ph._grpType??'PREPARATION', messages:localChatMessages[ph.assignment_id],
        draft:'', type:'message', priority:'normal', replyTo:null, sending:false,
    };
    nextTick(() => { if (chatMsgEl.value) chatMsgEl.value.scrollTop = chatMsgEl.value.scrollHeight; });
}
function closeChatPanel() { chatPanel.value.show = false; }
function setReply(msg: any) { chatPanel.value.replyTo = msg; }

async function sendChatMsg() {
    const { draft, assignmentId, formCode, type, priority, replyTo, phaseType } = chatPanel.value;
    if (!draft.trim() || chatPanel.value.sending) return;
    chatPanel.value.sending = true;
    try {
        const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '';
        const pt   = (phaseType || 'PREPARATION').toUpperCase();
        const chatUrl = props.chatBaseUrl ? `${props.chatBaseUrl}/${pt}` : `${baseUrl.value}/m/audit.core/missions/${(props.mission as any).id}/chat/${pt}`;
        const res  = await fetch(chatUrl, {
            method:'POST',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf,Accept:'application/json'},
            body:JSON.stringify({ assignment_id:assignmentId, form_code:formCode||null, content:draft, type, priority, parent_id:replyTo?.id??null }),
        });
        const json = await res.json();
        if (!res.ok) throw new Error(json?.message ?? 'Erreur');
        const newMsg = { ...json.message, is_mine:true, is_read:true, is_pinned:false,
            author_initials:ini((props.auditor as any).last_name,(props.auditor as any).first_name),
            author_name:`${(props.auditor as any).last_name} ${(props.auditor as any).first_name}`,
            author_role:(props.auditor as any).role };
        if (!localChatMessages[assignmentId]) localChatMessages[assignmentId] = [...getChatMessages(assignmentId)];
        localChatMessages[assignmentId].push(newMsg);
        chatPanel.value.messages = localChatMessages[assignmentId];
        chatPanel.value.draft=''; chatPanel.value.replyTo=null;
        nextTick(() => { if (chatMsgEl.value) chatMsgEl.value.scrollTop = chatMsgEl.value.scrollHeight; });
        showToast('Message envoyé.','success');
    } catch (e:any) { showToast('Erreur : '+e.message,'error'); }
    finally { chatPanel.value.sending = false; }
}

function openValidate(ph: any) {
    validateModal.value = { show:true, id:ph.assignment_id, label:ph.label, formCode:ph.form_code??'', choice:'validate', note:'' };
}
function closeValidate() { validateModal.value.show = false; }

async function submitValidation() {
    const { id, choice, note, formCode } = validateModal.value;
    if (choice === 'reject' && !note.trim()) return;
    try {
        const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '';
        const targetPh = allPhases.value.find(p => p.assignment_id === id);
        const validateUrl = targetPh?.validate_url ?? `${baseUrl.value}/m/audit.core/auditor/missions/${(props.mission as any).id}/phases/${id}/validate`;
        const res  = await fetch(validateUrl, { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf,Accept:'application/json'}, body:JSON.stringify({ action:choice, note, form_code:formCode }) });
        const json = await res.json();
        if (!res.ok) throw new Error(json?.message ?? 'Erreur');
        if (targetPh) targetPh.validation_status = choice === 'validate' ? 'validated' : 'rejected';
        closeValidate();
        showToast(choice==='validate'?'Formulaire validé.':'Formulaire rejeté.', choice==='validate'?'success':'warning');
    } catch (e:any) { showToast('Erreur : '+e.message,'error'); }
}

function openNote(ph: any) {
    noteModal.value = { show:true, id:ph.assignment_id, label:ph.label, draft:localNotes[ph.assignment_id] ?? (phMks(ph.assignment_id).find((m:any)=>m.is_mine)?.content ?? '') };
}
function closeNote() { noteModal.value.show = false; }
function saveNote() {
    if (noteModal.value.id) localNotes[noteModal.value.id] = noteModal.value.draft;
    closeNote(); showToast('Note enregistrée.','success');
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function phMks(id: number): any[] { return (props.markingsData as any)[id] ?? []; }
function entityName(id: number|null) { return props.entities.find(e => e.entity_id === id)?.entity_name ?? ''; }
function ini(last:string, first:string) { return ((last?.[0]??'')+(first?.[0]??'')).toUpperCase()||'?'; }
function stLbl(s:string) { return ({planifiee:'Planifiée',en_cours:'En cours',terminee:'Terminée',annulee:'Annulée'} as any)[s]??s; }
function chatTypeLbl(t:string) { return ({instruction:'Instruction',correction:'Correction',validation:'Validation',rejet:'Rejet',info:'Info'} as any)[t]??t; }

let _t: ReturnType<typeof setTimeout>;
function showToast(msg:string, type='success') {
    toast.value = { show:true, type, msg };
    clearTimeout(_t);
    _t = setTimeout(() => { toast.value.show = false; }, 4000);
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap');
*,*::before,*::after { box-sizing:border-box; margin:0; padding:0; }

.mp-shell { font-family:'Plus Jakarta Sans',sans-serif; background:#f1f5f9; color:#1e293b; min-height:calc(100vh - 68px); display:flex; flex-direction:column; }

/* ═══ HEADER ═══ */
.mp-header { position:sticky; top:0; z-index:50; background:#fff; border-bottom:1px solid #e2e8f0; box-shadow:0 2px 12px rgba(0,0,0,.07); }
.mph-row { display:flex; align-items:center; gap:20px; padding:16px 24px 12px; flex-wrap:wrap; }
.mph-back { width:34px; height:34px; border-radius:9px; background:#f8fafc; border:1px solid #e2e8f0; color:#64748b; display:flex; align-items:center; justify-content:center; text-decoration:none; flex-shrink:0; font-size:.95rem; transition:all .15s; }
.mph-back:hover { background:#f1f5f9; color:#1e293b; border-color:#cbd5e1; }
.mph-info { flex:1; min-width:0; display:flex; flex-direction:column; gap:5px; }
.mph-chips { display:flex; align-items:center; gap:6px; flex-wrap:wrap; }
.mph-code { font-family:'JetBrains Mono',monospace; font-size:.68rem; font-weight:700; padding:3px 9px; border-radius:6px; border:1px solid; letter-spacing:.02em; }
.mph-status { font-size:.6rem; font-weight:700; padding:3px 9px; border-radius:20px; text-transform:uppercase; letter-spacing:.07em; }
.st-planifiee { background:#ede9fe; color:#7c3aed; }
.st-en_cours  { background:#fef9c3; color:#a16207; }
.st-terminee  { background:#dcfce7; color:#16a34a; }
.st-annulee   { background:#fee2e2; color:#dc2626; }
.mph-myrole { display:inline-flex; align-items:center; gap:4px; font-size:.62rem; font-weight:600; padding:3px 9px; border-radius:8px; }
.mph-type   { display:inline-flex; align-items:center; gap:4px; font-size:.64rem; font-weight:500; }
.mph-title  { font-size:1.1rem; font-weight:800; color:#0f172a; letter-spacing:-.03em; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.mph-meta   { display:flex; flex-wrap:wrap; gap:14px; }
.mph-meta span { display:inline-flex; align-items:center; gap:5px; font-size:.72rem; color:#64748b; }
.mph-team  { display:flex; flex-direction:column; align-items:center; gap:5px; }
.mph-avs   { display:flex; }
.mph-av { width:30px; height:30px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:.56rem; font-weight:700; border:2px solid #fff; margin-left:-6px; position:relative; cursor:default; transition:transform .12s; }
.mph-av:first-child { margin-left:0; }
.mph-av:hover { transform:scale(1.15); z-index:3; }
.mph-av-me { position:absolute; bottom:-1px; right:-1px; width:8px; height:8px; background:#0891b2; border-radius:50%; border:1.5px solid #fff; }
.mph-av-more { background:#f1f5f9; color:#64748b; font-size:.52rem; }
.mph-team-cnt { font-size:.56rem; color:#94a3b8; font-weight:500; white-space:nowrap; }
.mph-ring { width:54px; height:54px; position:relative; flex-shrink:0; }
.mph-ring svg { width:54px; height:54px; transform:rotate(-90deg); }
.mph-ring-c { position:absolute; inset:0; display:flex; flex-direction:column; align-items:center; justify-content:center; }
.mph-ring-pct { font-size:.64rem; font-weight:800; line-height:1; }
.mph-ring-sub { font-size:.5rem; color:#94a3b8; font-weight:600; }
.mph-typebar { display:flex; border-top:1px solid #f1f5f9; overflow-x:auto; padding:0 24px; }
.mph-typebar::-webkit-scrollbar { height:0; }
.mph-typebtn { display:inline-flex; align-items:center; gap:7px; padding:9px 16px; background:transparent; border:none; border-bottom:2px solid transparent; color:#64748b; font-size:.72rem; font-weight:500; cursor:pointer; white-space:nowrap; transition:all .15s; font-family:'Plus Jakarta Sans',sans-serif; position:relative; top:1px; }
.mph-typebtn:hover { color:#334155; }
.mph-typebtn.active { font-weight:700; }
.mtb-dot  { width:7px; height:7px; border-radius:50%; flex-shrink:0; }
.mtb-cnt  { background:#f1f5f9; color:#94a3b8; font-size:.62rem; padding:0 6px; border-radius:8px; }
.mtb-done { font-size:.62rem; font-weight:700; }
.mtb-unread { background:#dc2626; color:#fff; font-size:.54rem; font-weight:700; padding:1px 5px; border-radius:10px; min-width:16px; text-align:center; }

/* ═══ BODY ═══ */
.mp-body { display:flex; flex:1; overflow:hidden; }

/* Sidebar */
.mp-sidebar { width:220px; flex-shrink:0; background:#fff; border-right:1px solid #e2e8f0; display:flex; flex-direction:column; overflow-y:auto; }
.mp-sidebar::-webkit-scrollbar { width:3px; }
.mp-sidebar::-webkit-scrollbar-thumb { background:#e2e8f0; border-radius:3px; }
.mps-block { display:flex; flex-direction:column; }
.mps-view-block { border-top:1px solid #f1f5f9; }
.mps-team-block { border-top:1px solid #f1f5f9; flex:1; }
.mps-label { display:flex; align-items:center; gap:6px; padding:12px 14px 6px; font-size:.6rem; font-weight:700; text-transform:uppercase; letter-spacing:.1em; color:#94a3b8; }
.mps-badge { margin-left:auto; background:#f1f5f9; color:#64748b; font-size:.58rem; padding:1px 6px; border-radius:8px; font-weight:600; }
.mps-list  { display:flex; flex-direction:column; gap:1px; padding:2px 8px 10px; }
.mps-item  { display:flex; align-items:flex-start; gap:9px; padding:8px 10px; border-radius:8px; background:transparent; border:none; color:#64748b; cursor:pointer; text-align:left; transition:all .12s; width:100%; font-family:'Plus Jakarta Sans',sans-serif; font-size:.73rem; }
.mps-item:hover { background:#f8fafc; color:#334155; }
.mps-item.on    { background:#f1f5f9; color:#1e293b; font-weight:600; }
.mps-dot        { width:7px; height:7px; border-radius:50%; flex-shrink:0; margin-top:4px; }
.mps-item-txt   { display:flex; flex-direction:column; gap:2px; min-width:0; }
.mps-item-name  { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.mps-item-date  { font-size:.62rem; color:#94a3b8; }
.mps-view-btns  { display:flex; flex-direction:column; gap:3px; padding:4px 8px 10px; }
.mps-vbtn { display:flex; align-items:center; gap:7px; padding:7px 10px; border-radius:8px; background:transparent; border:1px solid transparent; color:#64748b; font-size:.71rem; font-weight:500; cursor:pointer; font-family:'Plus Jakarta Sans',sans-serif; transition:all .12s; text-align:left; }
.mps-vbtn:hover { background:#f8fafc; color:#334155; }
.mps-vbtn.on    { background:#f1f5f9; border-color:#e2e8f0; color:#1e293b; font-weight:700; }
.mps-team   { display:flex; flex-direction:column; gap:2px; padding:4px 8px 12px; }
.mps-member { display:flex; align-items:center; gap:9px; padding:7px 8px; border-radius:8px; }
.mps-me     { background:#f8fafc; }
.mps-av     { width:28px; height:28px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:.54rem; font-weight:700; flex-shrink:0; }
.mps-minfo  { display:flex; flex-direction:column; gap:3px; min-width:0; }
.mps-mname  { font-size:.7rem; font-weight:600; color:#334155; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.mps-me-tag { font-size:.5rem; background:#0891b2; color:#fff; padding:0 4px; border-radius:3px; margin-left:4px; font-weight:700; }
.mps-mrole  { font-size:.58rem; font-weight:600; padding:1px 5px; border-radius:4px; display:inline-block; width:fit-content; }

/* Main */
.mp-main { flex:1; overflow-y:auto; background:#f1f5f9; }
.mp-main::-webkit-scrollbar { width:4px; }
.mp-main::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:4px; }
.mp-empty { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:12px; padding:80px 20px; color:#94a3b8; }
.mp-empty i { font-size:2.5rem; }
.mp-empty p { font-size:.9rem; font-weight:600; color:#cbd5e1; }

/* ═══ VUE HIÉRARCHIE ═══ */
.type-section { padding-bottom:0; }
.type-sticky { position:sticky; top:0; z-index:30; background:rgba(241,245,249,.98); backdrop-filter:blur(10px); border-bottom:1px solid #e2e8f0; }
.type-sticky-inner { display:flex; align-items:center; gap:12px; padding:10px 24px; flex-wrap:wrap; }
.type-pill { display:inline-flex; align-items:center; gap:7px; padding:6px 14px; border-radius:20px; border:1px solid; flex-shrink:0; }
.type-pill-dot   { width:8px; height:8px; border-radius:50%; }
.type-pill-label { font-size:.78rem; font-weight:800; letter-spacing:-.01em; }
.type-stats { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.ts-item { display:inline-flex; align-items:center; gap:4px; font-size:.66rem; font-weight:500; color:#64748b; }
.ts-done { color:#16a34a; font-weight:700; }
.ts-ip   { color:#a16207; font-weight:600; }
.ts-pend { color:#94a3b8; }
.type-pbar { flex:1; min-width:80px; max-width:180px; height:4px; background:#e2e8f0; border-radius:4px; overflow:hidden; }
.type-pbar-fill { height:100%; border-radius:4px; transition:width .6s ease; }
.type-pct { font-size:.68rem; font-weight:800; flex-shrink:0; }

.hier-container { padding:20px 24px 24px; display:flex; flex-direction:column; gap:16px; }
.hier-root { display:flex; flex-direction:column; }
.hier-children { display:flex; gap:0; padding-left:28px; position:relative; }
.hier-children-line { position:absolute; left:28px; top:0; bottom:0; width:0; border-left:2px dashed #e2e8f0; pointer-events:none; }
.hier-children-cards { flex:1; display:flex; flex-direction:column; gap:10px; padding-top:10px; padding-bottom:4px; }
.hier-child-wrap { display:flex; gap:0; align-items:flex-start; }
.hier-child-connector { width:28px; flex-shrink:0; display:flex; align-items:center; border-top:2px dashed; margin-top:20px; position:relative; }
.hcc-dot { width:7px; height:7px; border-radius:50%; position:absolute; right:-4px; top:-4px; }

.sub-expand-enter-active { transition:all .25s ease; }
.sub-expand-leave-active { transition:all .18s ease; }
.sub-expand-enter-from,.sub-expand-leave-to { opacity:0; transform:translateY(-8px); }

/* ═══ VUE TIMELINE ═══ */
.tg-section { padding-bottom:8px; }
.tg-sticky { position:sticky; top:0; z-index:30; background:rgba(241,245,249,.97); backdrop-filter:blur(10px); border-bottom:1px solid #e2e8f0; }
.tg-sticky-inner { display:flex; align-items:center; gap:14px; padding:9px 28px; }
.tg-date-pill { display:inline-flex; align-items:center; gap:7px; background:#fff; border:1px solid #e2e8f0; padding:5px 12px; border-radius:20px; flex-shrink:0; box-shadow:0 1px 4px rgba(0,0,0,.05); }
.tg-date-pill i { color:#94a3b8; font-size:.8rem; }
.tgd-month { font-size:.78rem; font-weight:700; color:#1e293b; }
.tgd-year  { font-size:.66rem; color:#94a3b8; }
.tg-mission-recall { display:flex; align-items:center; gap:7px; overflow:hidden; }
.tgmr-code { font-family:'JetBrains Mono',monospace; font-size:.65rem; font-weight:700; }
.tgmr-sep  { color:#cbd5e1; }
.tgmr-name { font-size:.72rem; color:#94a3b8; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.tg-right  { display:flex; align-items:center; gap:10px; margin-left:auto; flex-shrink:0; }
.tgr-cnt   { font-size:.66rem; color:#94a3b8; font-weight:500; }
.tgr-ent   { display:inline-flex; align-items:center; gap:4px; font-size:.66rem; color:#94a3b8; }
.tg-phases { padding:20px 24px; display:flex; flex-direction:column; gap:0; }
.ph-row { display:flex; gap:16px; align-items:flex-start; margin-bottom:18px; }
.ph-row-disabled { opacity:.5; }
.ph-tl   { display:flex; flex-direction:column; align-items:center; width:24px; flex-shrink:0; padding-top:20px; }
.ph-tl-dot  { width:11px; height:11px; border-radius:50%; flex-shrink:0; z-index:1; }
.ph-tl-line { width:1px; background:#e2e8f0; flex:1; min-height:24px; margin-top:4px; }
.ph-row:last-child .ph-tl-line { display:none; }

/* ═══ PANEL CHAT ═══ */
.chat-overlay { position:fixed; inset:0; background:rgba(0,0,0,.25); z-index:200; }
.chat-panel { position:fixed; top:0; right:0; bottom:0; width:420px; max-width:95vw; background:#fff; border-left:1px solid #e2e8f0; z-index:201; display:flex; flex-direction:column; box-shadow:-8px 0 40px rgba(0,0,0,.12); }
.slide-right-enter-active,.slide-right-leave-active { transition:transform .25s ease; }
.slide-right-enter-from,.slide-right-leave-to { transform:translateX(100%); }
.chat-hd { display:flex; align-items:center; gap:12px; padding:16px 18px 12px; border-bottom:1px solid #f1f5f9; background:#fafbfc; flex-shrink:0; }
.chat-hd-info { display:flex; align-items:center; gap:10px; min-width:0; flex:1; }
.chat-hd-info i { font-size:1.1rem; flex-shrink:0; }
.chat-hd-label { display:block; font-size:.82rem; font-weight:700; color:#1e293b; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:220px; }
.chat-hd-form  { display:block; font-size:.62rem; color:#94a3b8; font-family:'JetBrains Mono',monospace; }
.chat-hd-count { font-size:.62rem; color:#94a3b8; background:#f1f5f9; padding:2px 8px; border-radius:8px; flex-shrink:0; }
.chat-close-btn { width:28px; height:28px; border-radius:7px; background:#f8fafc; border:1px solid #e2e8f0; color:#64748b; display:flex; align-items:center; justify-content:center; cursor:pointer; font-size:.75rem; flex-shrink:0; }
.chat-close-btn:hover { color:#1e293b; }
.chat-members { display:flex; align-items:center; gap:10px; padding:8px 16px; background:#f8fafc; border-bottom:1px solid #f1f5f9; flex-shrink:0; }
.chat-members-label { font-size:.6rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:#94a3b8; display:flex; align-items:center; gap:5px; flex-shrink:0; }
.chat-members-avs { display:flex; }
.chat-mbr-av { width:24px; height:24px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:.5rem; font-weight:700; border:2px solid #f8fafc; margin-left:-5px; cursor:default; position:relative; transition:transform .1s; }
.chat-mbr-av:first-child { margin-left:0; }
.chat-mbr-av:hover { transform:scale(1.2); z-index:2; }
.chat-mbr-me { position:absolute; bottom:-1px; right:-1px; width:7px; height:7px; background:#0891b2; border-radius:50%; border:1.5px solid #f8fafc; }
.chat-msgs { flex:1; overflow-y:auto; padding:12px 14px; display:flex; flex-direction:column; gap:2px; }
.chat-msgs::-webkit-scrollbar { width:3px; }
.chat-msgs::-webkit-scrollbar-thumb { background:#e2e8f0; }
.chat-empty { display:flex; flex-direction:column; align-items:center; gap:8px; padding:40px 20px; color:#cbd5e1; text-align:center; }
.chat-empty i { font-size:2rem; }
.chat-empty p { font-size:.82rem; color:#94a3b8; font-weight:600; }
.chat-empty span { font-size:.72rem; color:#cbd5e1; }
.chat-day-sep { display:flex; align-items:center; gap:10px; margin:12px 0 6px; }
.chat-day-sep::before,.chat-day-sep::after { content:''; flex:1; height:1px; background:#f1f5f9; }
.chat-day-sep span { font-size:.58rem; font-weight:700; color:#94a3b8; white-space:nowrap; text-transform:uppercase; letter-spacing:.08em; }
.chat-msg { display:flex; gap:10px; margin-bottom:6px; }
.chat-mine { flex-direction:row-reverse; }
.chat-unread { position:relative; }
.chat-unread::before { content:''; position:absolute; left:-8px; top:4px; bottom:4px; width:2px; background:#7c3aed; border-radius:2px; }
.cmsg-av  { width:28px; height:28px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:.54rem; font-weight:700; flex-shrink:0; cursor:default; }
.cmsg-body { flex:1; min-width:0; }
.chat-mine .cmsg-body { align-items:flex-end; display:flex; flex-direction:column; }
.cmsg-meta { display:flex; align-items:center; gap:5px; flex-wrap:wrap; margin-bottom:4px; }
.chat-mine .cmsg-meta { flex-direction:row-reverse; }
.cmsg-who  { font-size:.67rem; font-weight:600; }
.cmsg-role { font-size:.56rem; color:#64748b; }
.cmsg-type { font-size:.58rem; padding:1px 6px; border-radius:4px; }
.ctype-instruction { background:#fef9c3; color:#a16207; }
.ctype-correction  { background:#fee2e2; color:#dc2626; }
.ctype-info        { background:#eff6ff; color:#2563eb; }
.cmsg-pri  { font-size:.58rem; font-weight:700; padding:1px 6px; border-radius:4px; }
.cpri-urgent   { background:#fef9c3; color:#a16207; }
.cpri-bloquant { background:#fee2e2; color:#dc2626; }
.cmsg-date { font-size:.56rem; color:#94a3b8; }
.cmsg-new  { font-size:.54rem; font-weight:700; color:#7c3aed; background:#faf5ff; padding:1px 6px; border-radius:6px; }
.chat-pri-bloquant .cmsg-body { padding-left:8px; border-left:2px solid #fca5a5; }
.chat-pri-urgent   .cmsg-body { padding-left:8px; border-left:2px solid #fcd34d; }
.cmsg-txt { font-size:.77rem; color:#475569; line-height:1.55; background:#f8fafc; padding:8px 11px; border-radius:9px; border:1px solid #f1f5f9; word-break:break-word; }
.chat-mine .cmsg-txt { background:#eff6ff; border-color:#bfdbfe; color:#1e40af; border-radius:9px 2px 9px 9px; }
.cmsg-actions { display:flex; align-items:center; gap:8px; margin-top:4px; }
.cmsg-reply { background:none; border:none; color:#94a3b8; font-size:.62rem; cursor:pointer; font-family:'Plus Jakarta Sans',sans-serif; display:flex; align-items:center; gap:3px; }
.cmsg-reply:hover { color:#7c3aed; }
.cmsg-thread { font-size:.58rem; color:#94a3b8; display:flex; align-items:center; gap:3px; font-style:italic; }
.chat-compose { border-top:1px solid #f1f5f9; padding:12px 14px 14px; background:#fafbfc; flex-shrink:0; display:flex; flex-direction:column; gap:8px; }
.chat-reply-preview { display:flex; align-items:center; gap:7px; font-size:.68rem; color:#7c3aed; background:#faf5ff; border:1px solid #ddd6fe; padding:5px 10px; border-radius:7px; overflow:hidden; }
.chat-reply-preview span { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; flex:1; }
.chat-reply-preview button { margin-left:auto; background:none; border:none; color:#94a3b8; cursor:pointer; flex-shrink:0; }
.chat-opts { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.chat-select { background:#fff; border:1px solid #e2e8f0; color:#334155; border-radius:7px; padding:4px 9px; font-size:.68rem; font-family:'Plus Jakarta Sans',sans-serif; cursor:pointer; outline:none; }
.chat-prios { display:flex; gap:4px; }
.chat-prio-btn { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:20px; border:1px solid #e2e8f0; background:#f8fafc; color:#64748b; font-size:.62rem; font-weight:600; cursor:pointer; transition:all .12s; font-family:'Plus Jakarta Sans',sans-serif; }
.chat-prio-btn.active.cpb-normal   { background:#eff6ff; color:#2563eb; border-color:#bfdbfe; }
.chat-prio-btn.active.cpb-urgent   { background:#fef9c3; color:#a16207; border-color:#fde68a; }
.chat-prio-btn.active.cpb-bloquant { background:#fee2e2; color:#dc2626; border-color:#fecaca; }
.chat-input-row { display:flex; gap:8px; align-items:flex-end; }
.chat-ta { flex:1; background:#fff; border:1px solid #e2e8f0; border-radius:9px; color:#1e293b; font-family:'Plus Jakarta Sans',sans-serif; font-size:.8rem; padding:9px 12px; resize:none; outline:none; transition:border-color .12s; }
.chat-ta:focus { border-color:#a5b4fc; }
.chat-ta::placeholder { color:#cbd5e1; }
.chat-send { width:36px; height:36px; border-radius:9px; border:none; color:#fff; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:.85rem; flex-shrink:0; transition:opacity .12s; }
.chat-send:disabled { opacity:.4; cursor:not-allowed; }
.chat-hint { font-size:.58rem; color:#cbd5e1; }
@keyframes spin { to { transform:rotate(360deg); } }
.spin { animation:spin .7s linear infinite; display:inline-block; }

/* ═══ MODALES ═══ */
.m-bg { position:fixed; inset:0; background:rgba(15,23,42,.55); backdrop-filter:blur(8px); z-index:1000; display:flex; align-items:center; justify-content:center; }
.mfade-enter-active,.mfade-leave-active { transition:opacity .18s; }
.mfade-enter-from,.mfade-leave-to { opacity:0; }
.m-box { background:#fff; border:1px solid #e2e8f0; border-radius:16px; width:440px; max-width:95vw; box-shadow:0 24px 60px rgba(0,0,0,.18); display:flex; flex-direction:column; overflow:hidden; }
.m-box-lg { width:520px; }
.m-hd { display:flex; align-items:center; gap:8px; padding:16px 18px; font-size:.84rem; font-weight:700; color:#1e293b; border-bottom:1px solid #f1f5f9; background:#fafbfc; }
.m-hd-val { background:linear-gradient(135deg,#f0fdf4,#fafbfc); }
.m-hd-sub { font-size:.68rem; font-weight:500; color:#64748b; margin-left:5px; flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.m-x { margin-left:auto; width:28px; height:28px; border-radius:7px; background:#f8fafc; border:1px solid #e2e8f0; color:#64748b; display:flex; align-items:center; justify-content:center; cursor:pointer; font-size:.75rem; }
.m-x:hover { color:#1e293b; border-color:#cbd5e1; }
.m-bd { padding:16px 18px; display:flex; flex-direction:column; gap:14px; }
.m-ta { width:100%; background:#f8fafc; border:1px solid #e2e8f0; border-radius:9px; color:#1e293b; font-family:'Plus Jakarta Sans',sans-serif; font-size:.8rem; padding:11px 13px; resize:vertical; outline:none; transition:border-color .12s; }
.m-ta:focus { border-color:#a5b4fc; background:#fff; }
.m-ta::placeholder { color:#cbd5e1; }
.m-ft { display:flex; justify-content:flex-end; gap:8px; padding:12px 18px; border-top:1px solid #f1f5f9; background:#fafbfc; }
.val-info { display:flex; align-items:center; gap:9px; padding:10px 12px; background:#f8fafc; border:1px solid #f1f5f9; border-radius:9px; font-size:.8rem; font-weight:600; color:#475569; }
.val-form-code { font-family:'JetBrains Mono',monospace; font-size:.65rem; color:#94a3b8; margin-left:4px; }
.val-warn   { display:flex; align-items:center; gap:8px; padding:10px 12px; background:#fef9c3; border:1px solid #fde68a; border-radius:9px; font-size:.75rem; color:#92400e; }
.val-choice { display:flex; gap:8px; }
.val-btn    { flex:1; display:flex; align-items:center; justify-content:center; gap:6px; padding:9px 14px; border-radius:9px; border:1px solid #e2e8f0; font-size:.78rem; font-weight:600; cursor:pointer; font-family:'Plus Jakarta Sans',sans-serif; background:#f8fafc; color:#64748b; transition:all .15s; }
.val-reject.active { background:#fee2e2; border-color:#fecaca; color:#dc2626; }
.val-ok.active     { background:#dcfce7; border-color:#bbf7d0; color:#16a34a; }
.val-note-wrap { display:flex; flex-direction:column; gap:6px; }
.val-note-lbl  { font-size:.62rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:#94a3b8; }
.mbtn { display:inline-flex; align-items:center; gap:5px; padding:8px 16px; border-radius:8px; font-size:.76rem; font-weight:700; border:1px solid transparent; cursor:pointer; transition:all .12s; font-family:'Plus Jakarta Sans',sans-serif; }
.mbtn-gray    { background:#f8fafc; border-color:#e2e8f0; color:#64748b; }
.mbtn-gray:hover { border-color:#cbd5e1; color:#334155; }
.mbtn-primary { background:#eff6ff; border-color:#bfdbfe; color:#2563eb; }
.mbtn-primary:hover { background:#2563eb; color:#fff; }
.mbtn-success { background:#dcfce7; border-color:#bbf7d0; color:#16a34a; }
.mbtn-success:not(:disabled):hover { background:#16a34a; color:#fff; }
.mbtn-danger  { background:#fee2e2; border-color:#fecaca; color:#dc2626; }
.mbtn-danger:not(:disabled):hover  { background:#dc2626; color:#fff; }
.mbtn:disabled { opacity:.35; cursor:not-allowed; }

/* ═══ RÔLES & AVATARS ═══ */
.rb-DM { background:#fef9c3; color:#a16207; border:1px solid #fde68a; }
.rb-CM { background:#eff6ff; color:#2563eb; border:1px solid #bfdbfe; }
.rb-AS { background:#dcfce7; color:#16a34a; border:1px solid #bbf7d0; }
.rb-AJ { background:#faf5ff; color:#7c3aed; border:1px solid #ddd6fe; }
.av-DM { background:#fef9c3; color:#a16207; }
.av-CM { background:#eff6ff; color:#2563eb; }
.av-AS { background:#dcfce7; color:#16a34a; }
.av-AJ { background:#faf5ff; color:#7c3aed; }

/* ═══ TOAST ═══ */
.mp-toast { position:fixed; bottom:24px; right:24px; z-index:9999; display:flex; align-items:center; gap:9px; padding:12px 18px; border-radius:11px; font-size:.78rem; font-weight:600; box-shadow:0 12px 44px rgba(0,0,0,.15); font-family:'Plus Jakarta Sans',sans-serif; max-width:380px; border:1px solid; }
.mp-toast button { background:none; border:none; color:inherit; cursor:pointer; margin-left:5px; opacity:.6; }
.t-success { background:#f0fdf4; color:#16a34a; border-color:#bbf7d0; }
.t-warning { background:#fefce8; color:#a16207; border-color:#fde68a; }
.t-error   { background:#fff1f2; color:#dc2626; border-color:#fecaca; }
.toast-enter-active,.toast-leave-active { transition:all .22s; }
.toast-enter-from,.toast-leave-to { opacity:0; transform:translateY(10px); }

@media (max-width:900px) {
    .mp-body { flex-direction:column; }
    .mp-sidebar { width:100%; max-height:160px; flex-direction:row; overflow-x:auto; }
    .mps-team-block,.mps-view-block { display:none; }
    .mph-ring { display:none; }
    .hier-container,.tg-phases { padding:12px; }
    .hier-children { padding-left:16px; }
    .chat-panel { width:100%; }
}
</style>