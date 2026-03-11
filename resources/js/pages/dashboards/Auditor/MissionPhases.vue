<template>
    <VerticalLayout>
        <div class="mp-shell">

            <!-- ══════════════════════════════════════════
                 HEADER MISSION — sticky
            ══════════════════════════════════════════ -->
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
                    <div v-if="(equipe as any[]).length" class="mph-team">
                        <div class="mph-avs">
                            <div v-for="m in (equipe as any[]).slice(0,5)" :key="m.auditeur_id"
                                class="mph-av" :class="`av-${m.role}`"
                                :title="`${m.last_name} ${m.first_name} · ${m.role_libelle}`">
                                {{ ini(m.last_name, m.first_name) }}
                                <span v-if="m.is_me" class="mph-av-me"></span>
                            </div>
                            <div v-if="(equipe as any[]).length > 5" class="mph-av mph-av-more">
                                +{{ (equipe as any[]).length - 5 }}
                            </div>
                        </div>
                        <span class="mph-team-cnt">{{ (equipe as any[]).length }} membres</span>
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
                    <button v-for="grp in (phasesByType as any[])" :key="grp.phase_type"
                        class="mph-typebtn"
                        :class="{ active: filterType === grp.phase_type }"
                        :style="filterType === grp.phase_type ? `border-bottom-color:${grp.color||mc};color:${grp.color||mc}` : ''"
                        @click="filterType = filterType === grp.phase_type ? null : grp.phase_type">
                        <span class="mtb-dot" :style="`background:${grp.color||mc}`"></span>
                        {{ grp.label }}
                        <span class="mtb-cnt">{{ (grp.phases||[]).length }}</span>
                        <span class="mtb-done" :style="`color:${grp.color||mc}`">{{ grp.stats?.completed ?? 0 }} ✓</span>
                        <!-- Badge non-lus du groupe -->
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
                        <div class="mps-label">
                            <i class="ti ti-buildings"></i> Entités
                            <span class="mps-badge">{{ (entities as any[]).length }}</span>
                        </div>
                        <div class="mps-list">
                            <button class="mps-item" :class="{ on: activeEntity === null }" @click="activeEntity = null">
                                <span class="mps-dot" style="background:#94a3b8"></span>
                                Toutes les entités
                            </button>
                            <button v-for="e in (entities as any[])" :key="e.entity_id"
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

                    <div class="mps-block mps-team-block">
                        <div class="mps-label">
                            <i class="ti ti-users"></i> Équipe
                            <span class="mps-badge">{{ (equipe as any[]).length }}</span>
                        </div>
                        <div class="mps-team">
                            <div v-for="m in (equipe as any[])" :key="m.auditeur_id"
                                class="mps-member" :class="{ 'mps-me': m.is_me }">
                                <div class="mps-av" :class="`av-${m.role}`">{{ ini(m.last_name, m.first_name) }}</div>
                                <div class="mps-minfo">
                                    <span class="mps-mname">
                                        {{ m.last_name }} {{ m.first_name }}
                                        <span v-if="m.is_me" class="mps-me-tag">moi</span>
                                    </span>
                                    <span class="mps-mrole" :class="`rb-${m.role}`">{{ m.role }} · {{ m.role_libelle }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </aside>

                <!-- Main timeline -->
                <main class="mp-main" ref="mainEl">
                    <div v-if="!timelineGroups.length" class="mp-empty">
                        <i class="ti ti-calendar-off"></i>
                        <p>Aucune phase à afficher</p>
                    </div>

                    <section v-for="tg in timelineGroups" :key="tg.key" class="tg-section">
                        <!-- En-tête mois sticky -->
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
                                    <span v-if="activeEntity !== null" class="tgr-ent">
                                        <i class="ti ti-building"></i> {{ entityName(activeEntity) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Phases -->
                        <div class="tg-phases">
                            <div v-for="ph in tg.phases" :key="ph.assignment_id"
                                class="ph-row" :class="phRowClass(ph)">

                                <!-- Timeline verticale -->
                                <div class="ph-tl">
                                    <div class="ph-tl-dot" :style="`background:${ph._grpColor};box-shadow:0 0 0 4px ${ph._grpColor}20`"></div>
                                    <div class="ph-tl-line"></div>
                                </div>

                                <!-- Card -->
                                <div class="ph-card" :class="phCardClass(ph)">

                                    <!-- En-tête card -->
                                    <div class="phc-head" :style="`border-left:3px solid ${ph._grpColor}`">
                                        <div class="phc-head-l">
                                            <span class="phc-type-chip"
                                                :style="`color:${ph._grpColor};background:${ph._grpColor}12;border-color:${ph._grpColor}25`">
                                                {{ ph._grpLabel }}
                                            </span>
                                            <code class="phc-code">{{ ph.code_full || ph.code }}</code>
                                            <span v-if="ph.entity_name && activeEntity === null" class="phc-ent">
                                                <i class="ti ti-building"></i>{{ ph.entity_name }}
                                            </span>
                                            <!-- Badge validation -->
                                            <span v-if="ph.validation_status && ph.validation_status !== 'draft'"
                                                class="phc-valst" :class="`valst-${ph.validation_status}`">
                                                <i :class="valStIcon(ph.validation_status)"></i>
                                                {{ valStLbl(ph.validation_status) }}
                                            </span>
                                        </div>
                                        <div class="phc-head-r">
                                            <span class="phc-st" :class="`phst-${ph.phase_status}`">{{ phStLbl(ph.phase_status) }}</span>
                                            <span v-if="ph.progression > 0" class="phc-pct" :style="`color:${ph._grpColor}`">{{ ph.progression }}%</span>

                                            <!-- DÉMARRER -->
                                            <button v-if="canStart(ph)"
                                                class="phc-btn phb-start"
                                                title="Démarrer la phase"
                                                @click.stop="startPhase(ph)">
                                                <i class="ti ti-player-play-filled"></i>
                                            </button>

                                            <!-- FORMULAIRE -->
                                            <a v-if="ph.form_url && isAffected(ph) && ph.phase_status !== 'pending'"
                                                :href="buildFormUrl(ph)"
                                                class="phc-btn phb-form"
                                                :class="{
                                                    'phb-form-locked': ph.validation_status === 'validated',
                                                    'phb-form-review': ph.validation_status === 'in_review'
                                                }"
                                                :title="formBtnTitle(ph)">
                                                <i :class="formBtnIcon(ph)"></i>
                                                <span class="phb-form-lbl">{{ formBtnLabel(ph) }}</span>
                                            </a>

                                            <!-- CHAT -->
                                            <button class="phc-btn phb-chat"
                                                :class="{ 'phbn-unread': unreadCount(ph.assignment_id) > 0 }"
                                                :title="`Chat (${msgCount(ph.assignment_id)} message(s), ${unreadCount(ph.assignment_id)} non lu(s))`"
                                                @click.stop="openChat(ph)">
                                                <i class="ti ti-message-circle"></i>
                                                <span v-if="unreadCount(ph.assignment_id) > 0" class="phb-badge">{{ unreadCount(ph.assignment_id) }}</span>
                                                <span v-else-if="msgCount(ph.assignment_id) > 0" class="phb-badge phb-badge-read">{{ msgCount(ph.assignment_id) }}</span>
                                            </button>

                                            <!-- ACTIVER/DÉSACTIVER (DM/CM) -->
                                            <button v-if="canManage"
                                                class="phc-btn" :class="ph.is_disabled ? 'phb-enable' : 'phb-disable'"
                                                :title="ph.is_disabled ? 'Activer' : 'Désactiver'"
                                                @click.stop="toggleDisabled(ph)">
                                                <i :class="ph.is_disabled ? 'ti ti-player-play' : 'ti ti-player-pause'"></i>
                                            </button>

                                            <!-- NOTE -->
                                            <button class="phc-btn phb-note"
                                                :class="{ 'phbn-on': localNotes[ph.assignment_id] || getMyMk(ph) }"
                                                :disabled="ph.is_disabled"
                                                title="Ma note"
                                                @click.stop="openNote(ph)">
                                                <i class="ti ti-notes"></i>
                                            </button>

                                            <!-- VALIDER (DM uniquement) -->
                                            <button v-if="canValidate(ph)"
                                                class="phc-btn phb-validate"
                                                title="Valider le formulaire"
                                                @click.stop="openValidate(ph)">
                                                <i class="ti ti-circle-check"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Corps card -->
                                    <div class="phc-body">
                                        <h3 class="phc-title" :class="{ 'phc-title-off': ph.is_disabled }">{{ ph.label }}</h3>
                                        <p v-if="ph.description" class="phc-desc">{{ ph.description }}</p>

                                        <!-- Dates + barre -->
                                        <div class="phc-dates-wrap">
                                            <div class="phc-dates">
                                                <span class="phcd">
                                                    <i class="ti ti-calendar-event"></i>
                                                    <em>Prévu</em>
                                                    <strong>{{ ph.planned_start_fr || '—' }}</strong>
                                                    <i class="ti ti-arrow-right phcd-arr"></i>
                                                    <strong>{{ ph.planned_end_fr || '—' }}</strong>
                                                </span>
                                                <span v-if="ph.actual_start_fr" class="phcd phcd-real">
                                                    <i class="ti ti-calendar-check"></i>
                                                    <em>Réel</em>
                                                    <strong>{{ ph.actual_start_fr }}</strong>
                                                    <i class="ti ti-arrow-right phcd-arr"></i>
                                                    <strong>{{ ph.actual_end_fr || '—' }}</strong>
                                                </span>
                                                <span v-if="ph.planned_duration" class="phcd-dur">{{ ph.planned_duration }}j</span>
                                            </div>
                                            <div v-if="ph.progression > 0 || ph.phase_status === 'in_progress'" class="phc-bar">
                                                <div class="phc-bar-fill"
                                                    :style="`width:${ph.progression ?? 0}%;background:${ph._grpColor}`"
                                                    :class="{ 'phc-bar-anim': ph.phase_status === 'in_progress' }">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Auditeurs affectés -->
                                        <template v-if="ph.auditeurs_affectes?.length">
                                            <div class="phc-auds-row">
                                                <span v-if="ph.phase_status === 'pending'" class="phc-todo-badge">
                                                    <i class="ti ti-circle-dot"></i> À faire
                                                </span>
                                                <span v-if="!isAffected(ph) && ph.phase_status !== 'pending'" class="phc-readonly-badge">
                                                    <i class="ti ti-eye"></i> Lecture seule
                                                </span>
                                                <div class="phc-auds">
                                                    <div v-for="a in ph.auditeurs_affectes" :key="a.auditeur_id"
                                                        class="phc-aud" :class="[`av-${a.role_code}`, { 'phc-aud-me': a.is_me }]"
                                                        :title="`${a.full_name} · ${a.role_code}`">
                                                        <span class="phca-av">{{ a.initiales || ini2(a.full_name) }}</span>
                                                        <span class="phca-name">{{ shortN(a.full_name) }}</span>
                                                        <span class="phca-role" :class="`rb-${a.role_code}`">{{ a.role_code }}</span>
                                                        <span v-if="a.is_me" class="phca-me-dot"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>

                                        <!-- CTA Formulaire -->
                                        <div v-if="ph.form_url && isAffected(ph) && ph.phase_status !== 'pending'"
                                            class="phc-form-cta" :style="`border-color:${ph._grpColor}30;background:${ph._grpColor}06`">
                                            <div class="phcf-left">
                                                <div class="phcf-icon" :style="`background:${ph._grpColor}15;color:${ph._grpColor}`">
                                                    <i :class="formBtnIcon(ph)"></i>
                                                </div>
                                                <div class="phcf-info">
                                                    <span class="phcf-label">{{ ph.form_label || 'Formulaire' }}</span>
                                                    <span class="phcf-status" :class="`phcfs-${ph.validation_status || 'draft'}`">
                                                        {{ formStatusLabel(ph.validation_status) }}
                                                    </span>
                                                </div>
                                            </div>
                                            <a :href="buildFormUrl(ph)" class="phcf-btn"
                                                :style="`background:${ph._grpColor};color:#fff`">
                                                <i :class="ph.validation_status === 'validated' ? 'ti ti-eye' : 'ti ti-edit'"></i>
                                                {{ ph.validation_status === 'validated' ? 'Consulter' : 'Remplir' }}
                                            </a>
                                        </div>

                                    </div><!-- /phc-body -->

                                    <!-- Marquages -->
                                    <div v-if="phMks(ph.assignment_id).length" class="phc-mks">
                                        <div v-for="mk in phMks(ph.assignment_id)" :key="mk.id"
                                            class="phc-mk" :class="[{ 'phc-mk-mine': mk.is_mine }, `phc-mk-${mk.status}`]">
                                            <div class="phcmk-av" :class="`av-${mk.author_role}`">{{ mk.author_initials }}</div>
                                            <div class="phcmk-body">
                                                <div class="phcmk-meta">
                                                    <span class="phcmk-who" :class="`rb-${mk.author_role}`">{{ mk.author_name }}</span>
                                                    <span class="phcmk-r">{{ mk.author_role }}</span>
                                                    <span class="phcmk-st" :class="`mkst-${mk.status}`">{{ mkStLbl(mk.status) }}</span>
                                                    <span class="phcmk-date">{{ mk.created_at_fr }}</span>
                                                </div>
                                                <p class="phcmk-txt">{{ mk.content }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Aperçu chat (2 derniers messages) -->
                                    <div v-if="chatPreview(ph.assignment_id).length" class="phc-chat-prev">
                                        <div class="phcp-hd">
                                            <i class="ti ti-message-circle" :style="`color:${ph._grpColor}`"></i>
                                            <span>{{ msgCount(ph.assignment_id) }} message(s)</span>
                                            <span v-if="unreadCount(ph.assignment_id) > 0" class="phcp-unread-badge">
                                                {{ unreadCount(ph.assignment_id) }} nouveau(x)
                                            </span>
                                            <button class="phcp-all" @click.stop="openChat(ph)">Voir tout</button>
                                        </div>
                                        <div v-for="msg in chatPreview(ph.assignment_id)" :key="msg.id"
                                            class="phcp-msg" :class="[`chat-${msg.priority}`, { 'phcp-msg-unread': !msg.is_read && !msg.is_mine }]">
                                            <div class="phcpm-av" :class="`av-${msg.author_role}`">{{ msg.author_initials }}</div>
                                            <div class="phcpm-body">
                                                <div class="phcpm-meta">
                                                    <span :class="`rb-${msg.author_role}`">{{ msg.author_role }}</span>
                                                    <span class="phcpm-who">{{ shortN(msg.author_name) }}</span>
                                                    <span v-if="msg.priority !== 'normal'" class="phcpm-pri" :class="`pri-${msg.priority}`">{{ msg.priority }}</span>
                                                    <span v-if="msg.type !== 'message'" class="phcpm-type">{{ chatTypeLbl(msg.type) }}</span>
                                                    <span class="phcpm-date">{{ msg.created_at_fr }}</span>
                                                </div>
                                                <p class="phcpm-txt">{{ msg.content }}</p>
                                            </div>
                                        </div>
                                    </div>

                                </div><!-- /ph-card -->
                            </div><!-- /ph-row -->
                        </div>
                    </section>
                </main>
            </div><!-- /mp-body -->
        </div><!-- /mp-shell -->

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
                        <div class="chat-hd-badges">
                            <span v-if="chatPanel.messages.length" class="chat-hd-count">
                                {{ chatPanel.messages.length }} message(s)
                            </span>
                        </div>
                        <button class="chat-close-btn" @click="closeChatPanel"><i class="ti ti-x"></i></button>
                    </div>

                    <!-- Membres visibles dans ce chat -->
                    <div class="chat-members">
                        <div class="chat-members-label">
                            <i class="ti ti-users"></i> Participants
                        </div>
                        <div class="chat-members-avs">
                            <div v-for="m in (equipe as any[])" :key="m.auditeur_id"
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

                        <!-- Groupe de messages par date -->
                        <template v-for="(dayGroup, dayKey) in chatByDay" :key="dayKey">
                            <div class="chat-day-sep">
                                <span>{{ dayKey }}</span>
                            </div>
                            <div v-for="msg in dayGroup" :key="msg.id"
                                class="chat-msg" :class="[
                                    `chat-msg-${msg.type}`,
                                    `chat-pri-${msg.priority}`,
                                    { 'chat-mine': msg.is_mine, 'chat-unread': !msg.is_read && !msg.is_mine }
                                ]">
                                <!-- Avatar -->
                                <div class="cmsg-av" :class="`av-${msg.author_role}`"
                                    :title="`${msg.author_name} · ${msg.author_role}`">
                                    {{ msg.author_initials }}
                                </div>
                                <div class="cmsg-body">
                                    <div class="cmsg-meta">
                                        <span class="cmsg-who" :class="`rb-${msg.author_role}`">{{ msg.author_name }}</span>
                                        <span class="cmsg-role">{{ msg.author_role }}</span>
                                        <span v-if="msg.type !== 'message'" class="cmsg-type" :class="`ctype-${msg.type}`">
                                            {{ chatTypeLbl(msg.type) }}
                                        </span>
                                        <span v-if="msg.priority !== 'normal'" class="cmsg-pri" :class="`cpri-${msg.priority}`">
                                            {{ msg.priority }}
                                        </span>
                                        <span class="cmsg-date">{{ msg.created_at_fr }}</span>
                                        <!-- Indicateur non-lu -->
                                        <span v-if="!msg.is_read && !msg.is_mine" class="cmsg-new">Nouveau</span>
                                    </div>
                                    <p class="cmsg-txt">{{ msg.content }}</p>
                                    <div class="cmsg-actions">
                                        <button v-if="!msg.is_mine" class="cmsg-reply" @click="setReply(msg)">
                                            <i class="ti ti-corner-down-right"></i> Répondre
                                        </button>
                                        <!-- Fil de réponse -->
                                        <span v-if="msg.parent_id" class="cmsg-thread">
                                            <i class="ti ti-corner-down-right"></i>
                                            En réponse à #{{ msg.parent_id }}
                                        </span>
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
                                @keydown.ctrl.enter="sendChatMsg">
                            </textarea>
                            <button class="chat-send"
                                :disabled="!chatPanel.draft.trim() || chatPanel.sending"
                                :style="`background:${mc}`"
                                @click="sendChatMsg">
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
                                <button class="val-btn val-reject" :class="{ active: validateModal.choice === 'reject' }"
                                    @click="validateModal.choice = 'reject'">
                                    <i class="ti ti-x"></i> Rejeter
                                </button>
                                <button class="val-btn val-ok" :class="{ active: validateModal.choice === 'validate' }"
                                    @click="validateModal.choice = 'validate'">
                                    <i class="ti ti-check"></i> Valider définitivement
                                </button>
                            </div>
                            <div class="val-note-wrap">
                                <label class="val-note-lbl">
                                    {{ validateModal.choice === 'reject' ? 'Motif du rejet *' : 'Note (optionnel)' }}
                                </label>
                                <textarea v-model="validateModal.note" class="m-ta" rows="3"
                                    :placeholder="validateModal.choice === 'reject' ? 'Expliquer la raison…' : 'Commentaire optionnel…'">
                                </textarea>
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

const $page   = usePage();
const baseUrl = computed<string>(() =>
    ($page.props as any)?.ziggy?.url
    ?? ($page.props as any)?.appUrl
    ?? window.location.origin
);

const props = defineProps({
    mission:      { type: Object, default: () => ({}) },
    phasesByType: { type: Array,  default: () => [] },
    entities:     { type: Array,  default: () => [] },
    equipe:       { type: Array,  default: () => [] },
    markingsData: { type: Object, default: () => ({}) },
    auditor:      { type: Object, default: () => ({}) },
    chatMessages: { type: Object, default: () => ({}) },
    // URLs pré-construites côté serveur — aucune reconstruction JS nécessaire
    chatBaseUrl:  { type: String, default: '' },   // ex: https://domaine.com/m/audit.core/missions/47/chat
    missionsUrl:  { type: String, default: '' },   // ex: https://domaine.com/m/audit.core/auditor/missions
});

// ── Couleur mission ────────────────────────────────────────────────────────
const mc = computed<string>(() => {
    const c = (props.mission as any)?.audit_color;
    if (c && c !== '#000000' && c !== '#000' && c !== 'null') return c;
    const fg = (props.phasesByType as any[])[0];
    return fg?.color && fg.color !== '#000000' ? fg.color : '#2563eb';
});

// ── États réactifs ─────────────────────────────────────────────────────────
const activeEntity = ref<number|null>(null);
const filterType   = ref<string|null>(null);
const localNotes   = reactive<Record<number,string>>({});
const mainEl       = ref<HTMLElement|null>(null);
const chatMsgEl    = ref<HTMLElement|null>(null);

// Stockage local des messages de chat (permet l'ajout temps-réel après envoi)
// Structure : { [assignment_id]: [...messages] }
const localChatMessages = reactive<Record<number, any[]>>({});

const noteModal     = ref({ show:false, id:0, label:'', draft:'' });
const validateModal = ref({ show:false, id:0, label:'', formCode:'', choice:'validate', note:'' });
const chatPanel     = ref({
    show:false, assignmentId:0, label:'', formCode:'', phaseType:'',
    messages:[] as any[], draft:'', type:'message', priority:'normal',
    replyTo: null as any,
    sending: false,
});
const toast = ref({ show:false, type:'success', msg:'' });

const PRIOS = [
    { v:'normal',   l:'Normal',   icon:'ti ti-info-circle' },
    { v:'urgent',   l:'Urgent',   icon:'ti ti-alert-triangle' },
    { v:'bloquant', l:'Bloquant', icon:'ti ti-alert-octagon' },
];

const canManage = computed(() => ['DM','CM'].includes((props.auditor as any)?.role ?? ''));

// ── Phases calculées ────────────────────────────────────────────────────────
const allPhases = computed<any[]>(() =>
    (props.phasesByType as any[]).flatMap(grp =>
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

const filteredPhases = computed<any[]>(() =>
    allPhases.value.filter(ph => {
        if (activeEntity.value !== null && ph.entity_id && ph.entity_id !== activeEntity.value) return false;
        if (filterType.value && ph._grpType !== filterType.value) return false;
        return true;
    })
);

const MONTHS_FR = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];

const timelineGroups = computed(() => {
    const sorted = [...filteredPhases.value].sort((a, b) => {
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
        } else { key = 'sans-date'; monthLabel = 'Sans date'; year = ''; }
        if (!map.has(key)) map.set(key, { key, monthLabel, year, phases: [] });
        map.get(key)!.phases.push(ph);
    }
    return [...map.values()];
});

// ── Helpers chat messages ───────────────────────────────────────────────────

/**
 * Retourne les messages d'un assignment.
 * Priorité : messages locaux (après envoi) > messages serveur (props)
 */
function getChatMessages(id: number): any[] {
    if (localChatMessages[id] !== undefined) return localChatMessages[id];
    return (props.chatMessages as any)[id] ?? [];
}

/** Aperçu : 2 derniers messages */
function chatPreview(id: number): any[] {
    return getChatMessages(id).slice(-2);
}

/** Nombre total de messages */
function msgCount(id: number): number {
    return getChatMessages(id).length;
}

/** Nombre de messages non lus pour cet assignment */
function unreadCount(id: number): number {
    return getChatMessages(id).filter((m: any) => !m.is_read && !m.is_mine).length;
}

/** Nombre de non-lus pour un type de phase (pour les badges de la barre de types) */
function groupUnreadCount(phaseType: string): number {
    const group = (props.phasesByType as any[]).find(g => g.phase_type === phaseType);
    if (!group) return 0;
    return (group.phases ?? []).reduce((sum: number, ph: any) => {
        return sum + unreadCount(ph.assignment_id);
    }, 0);
}

/** Messages groupés par date pour l'affichage dans le panel chat */
const chatByDay = computed<Record<string, any[]>>(() => {
    const msgs = chatPanel.value.messages;
    if (!msgs.length) return {};
    const groups: Record<string, any[]> = {};
    for (const msg of msgs) {
        // Extraire la date depuis created_at_fr (format JJ/MM/YYYY HH:mm)
        const datePart = (msg.created_at_fr ?? '').split(' ')[0] || 'Sans date';
        if (!groups[datePart]) groups[datePart] = [];
        groups[datePart].push(msg);
    }
    return groups;
});

// ── Helpers formulaire ──────────────────────────────────────────────────────

/**
 * form_url est construit côté serveur (contrôleur) avec url() Laravel.
 * Ex: https://domaine.com/m/audit.core/ac/preparation/reunion-ouverture?mission_id=47&assignment_id=43
 * On retourne directement — aucune reconstruction nécessaire.
 */
function buildFormUrl(ph: any): string {
    return ph.form_url || '#';
}

function formBtnIcon(ph: any): string {
    if (ph.validation_status === 'validated') return 'ti ti-lock';
    if (ph.validation_status === 'in_review') return 'ti ti-clock';
    // Icône personnalisée depuis ddmparam.audit_type_forms
    return ph.form_icon || 'ti ti-file-description';
}
function formBtnLabel(ph: any): string {
    if (ph.validation_status === 'validated') return 'Validé';
    if (ph.validation_status === 'in_review') return 'En révision';
    // Label depuis ddmparam.audit_type_forms, sinon générique
    return ph.form_label || 'Formulaire';
}
function formBtnTitle(ph: any): string {
    if (ph.validation_status === 'validated') return 'Formulaire validé (lecture seule)';
    if (ph.validation_status === 'in_review') return 'En attente de validation';
    return 'Ouvrir le formulaire';
}
function formStatusLabel(status: string): string {
    return ({
        draft:      'Brouillon',
        in_review:  'En révision',
        validated:  'Validé ✓',
        rejected:   'Rejeté',
    } as any)[status] ?? 'À remplir';
}

// ── Accès ───────────────────────────────────────────────────────────────────
function isAffected(ph: any): boolean {
    return ph.auditeurs_affectes?.some((a:any) => a.auditeur_id === (props.auditor as any).id)
        || canManage.value;
}
function canStart(ph: any): boolean {
    return ph.phase_status === 'pending' && !ph.is_disabled && isAffected(ph);
}
function canValidate(ph: any): boolean {
    return (props.auditor as any)?.role === 'DM' && ph.validation_status === 'submitted';
}

// ── Actions ─────────────────────────────────────────────────────────────────
async function startPhase(ph: any) {
    if (!confirm(`Démarrer la phase "${ph.label}" ?`)) return;
    const startUrl = ph.start_url;
    if (!startUrl) { showToast('URL de démarrage manquante.', 'error'); return; }
    try {
        const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '';
        const res = await fetch(startUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
        });
        const json = await res.json();
        if (!res.ok) throw new Error(json?.message ?? 'Erreur');

        // Rediriger vers le formulaire de la phase si disponible
        const destination = ph.form_url || json.form_url;
        if (destination) {
            window.location.href = destination;
        } else {
            ph.phase_status    = 'in_progress';
            ph.actual_start_fr = new Date().toLocaleDateString('fr-FR');
            showToast('Phase démarrée.', 'success');
        }
    } catch (e: any) { showToast('Erreur : ' + e.message, 'error'); }
}

function toggleDisabled(ph: any) {
    ph.is_disabled = !ph.is_disabled;
    showToast(ph.is_disabled ? 'Phase désactivée.' : 'Phase activée.', ph.is_disabled ? 'warning' : 'success');
}

// ── Chat ────────────────────────────────────────────────────────────────────
function openChat(ph: any) {
    const messages = getChatMessages(ph.assignment_id);

    // Copier dans localChatMessages si pas encore fait (pour pouvoir pousser de nouveaux msgs)
    if (localChatMessages[ph.assignment_id] === undefined) {
        localChatMessages[ph.assignment_id] = [...messages];
    }

    // Marquer tous les messages comme lus localement à l'ouverture
    localChatMessages[ph.assignment_id].forEach((m: any) => {
        if (!m.is_mine) m.is_read = true;
    });

    chatPanel.value = {
        show: true,
        assignmentId: ph.assignment_id,
        label: ph.label,
        formCode: ph.form_code ?? '',
        phaseType: ph._grpType ?? 'PREPARATION',
        messages: localChatMessages[ph.assignment_id],
        draft: '',
        type: 'message',
        priority: 'normal',
        replyTo: null,
        sending: false,
    };

    nextTick(() => {
        if (chatMsgEl.value) chatMsgEl.value.scrollTop = chatMsgEl.value.scrollHeight;
    });
}

function closeChatPanel() { chatPanel.value.show = false; }
function setReply(msg: any) { chatPanel.value.replyTo = msg; }

async function sendChatMsg() {
    const { draft, assignmentId, formCode, type, priority, replyTo, phaseType } = chatPanel.value;
    if (!draft.trim() || chatPanel.value.sending) return;

    const missionId = (props.mission as any).id;
    const pt = (phaseType || 'PREPARATION').toUpperCase();

    chatPanel.value.sending = true;
    try {
        const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '';
        // chatBaseUrl = props.chatBaseUrl (pré-construit côté serveur)
        // ex: https://domaine.com/m/audit.core/missions/47/chat
        const chatUrl = props.chatBaseUrl
            ? `${props.chatBaseUrl}/${pt}`
            : `${baseUrl.value}/m/audit.core/missions/${missionId}/chat/${pt}`;
        const res = await fetch(chatUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                Accept: 'application/json',
            },
            body: JSON.stringify({
                assignment_id: assignmentId,
                form_code:     formCode || null,
                content:       draft,
                type,
                priority,
                parent_id:     replyTo?.id ?? null,
            }),
        });
        const json = await res.json();
        if (!res.ok) throw new Error(json?.message ?? 'Erreur');

        // Construire le message local avec les infos de l'auditeur connecté
        const newMsg = {
            ...json.message,
            is_mine:         true,
            is_read:         true,
            is_pinned:       false,
            author_initials: ini((props.auditor as any).last_name, (props.auditor as any).first_name),
            author_name:     `${(props.auditor as any).last_name} ${(props.auditor as any).first_name}`,
            author_role:     (props.auditor as any).role,
        };

        // Initialiser le tableau local si nécessaire
        if (!localChatMessages[assignmentId]) {
            localChatMessages[assignmentId] = [...getChatMessages(assignmentId)];
        }
        localChatMessages[assignmentId].push(newMsg);
        chatPanel.value.messages = localChatMessages[assignmentId];
        chatPanel.value.draft    = '';
        chatPanel.value.replyTo  = null;

        nextTick(() => {
            if (chatMsgEl.value) chatMsgEl.value.scrollTop = chatMsgEl.value.scrollHeight;
        });
        showToast('Message envoyé.', 'success');
    } catch (e: any) {
        showToast('Erreur : ' + e.message, 'error');
    } finally {
        chatPanel.value.sending = false;
    }
}

// ── Validation ──────────────────────────────────────────────────────────────
function openValidate(ph: any) {
    validateModal.value = {
        show:true, id:ph.assignment_id, label:ph.label,
        formCode:ph.form_code ?? '', choice:'validate', note:''
    };
}
function closeValidate() { validateModal.value.show = false; }

async function submitValidation() {
    const { id, choice, note, formCode } = validateModal.value;
    if (choice === 'reject' && !note.trim()) return;
    try {
        const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '';
        const targetPh = allPhases.value.find(p => p.assignment_id === id);
        const validateUrl = targetPh?.validate_url
            ?? `${baseUrl.value}/m/audit.core/auditor/missions/${(props.mission as any).id}/phases/${id}/validate`;
        const res = await fetch(validateUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
            body: JSON.stringify({ action: choice, note, form_code: formCode }),
        });
        const json = await res.json();
        if (!res.ok) throw new Error(json?.message ?? 'Erreur');
        if (targetPh) targetPh.validation_status = choice === 'validate' ? 'validated' : 'rejected';
        closeValidate();
        showToast(
            choice === 'validate' ? 'Formulaire validé.' : 'Formulaire rejeté.',
            choice === 'validate' ? 'success' : 'warning'
        );
    } catch (e: any) { showToast('Erreur : ' + e.message, 'error'); }
}

// ── Note ────────────────────────────────────────────────────────────────────
function openNote(ph: any) {
    noteModal.value = {
        show:true, id:ph.assignment_id, label:ph.label,
        draft: localNotes[ph.assignment_id] ?? (getMyMk(ph)?.content ?? '')
    };
}
function closeNote() { noteModal.value.show = false; }
function saveNote() {
    if (noteModal.value.id) localNotes[noteModal.value.id] = noteModal.value.draft;
    closeNote();
    showToast('Note enregistrée.', 'success');
}

// ── Helpers ─────────────────────────────────────────────────────────────────
function phRowClass(ph: any) {
    return {
        'ph-row-done':     ph.phase_status === 'completed',
        'ph-row-ip':       ph.phase_status === 'in_progress',
        'ph-row-disabled': ph.is_disabled,
    };
}
function phCardClass(ph: any) {
    return {
        'phc-pending':    ph.auditeurs_affectes?.length > 0 && ph.phase_status === 'pending',
        'phc-inprogress': ph.phase_status === 'in_progress',
        'phc-done':       ph.phase_status === 'completed',
        'phc-disabled':   ph.is_disabled,
        'phc-validated':  ph.validation_status === 'validated',
    };
}
function phMks(id: number): any[]  { return (props.markingsData as any)[id] ?? []; }
function getMyMk(ph: any): any     { return phMks(ph.assignment_id).find((m:any) => m.is_mine); }
function entityName(id: number|null) {
    return (props.entities as any[]).find(e => e.entity_id === id)?.entity_name ?? '';
}
function ini(last:string, first:string) {
    return ((last?.[0] ?? '') + (first?.[0] ?? '')).toUpperCase() || '?';
}
function ini2(full:string) {
    return (full ?? '').trim().split(/\s+/).map((p:string) => p[0]).join('').toUpperCase().slice(0,2) || '?';
}
function shortN(full:string) {
    const p = (full ?? '').trim().split(/\s+/).filter(Boolean);
    return !p.length ? '—' : p.length === 1 ? p[0] : `${p[0]} ${p[1][0]}.`;
}
function stLbl(s:string)      { return ({planifiee:'Planifiée',en_cours:'En cours',terminee:'Terminée',annulee:'Annulée'} as any)[s] ?? s; }
function phStLbl(s:string)    { return ({pending:'À faire',in_progress:'En cours',completed:'Terminé',skipped:'Ignorée'} as any)[s] ?? s; }
function mkStLbl(s:string)    { return ({draft:'Brouillon',submitted:'Soumis',validated:'Validé',rejected:'Rejeté'} as any)[s] ?? s; }
function valStLbl(s:string)   { return ({submitted:'En attente',validated:'Validé ✓',rejected:'Rejeté'} as any)[s] ?? s; }
function valStIcon(s:string)  { return ({submitted:'ti ti-clock',validated:'ti ti-circle-check',rejected:'ti ti-circle-x'} as any)[s] ?? 'ti ti-circle'; }
function chatTypeLbl(t:string){ return ({instruction:'Instruction',correction:'Correction',validation:'Validation',rejet:'Rejet',info:'Info'} as any)[t] ?? t; }

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

/* ═══ SHELL ═══ */
.mp-shell {
    font-family:'Plus Jakarta Sans',sans-serif;
    background:#f1f5f9;
    color:#1e293b;
    min-height:calc(100vh - 68px);
    display:flex;
    flex-direction:column;
}

/* ═══ HEADER ═══ */
.mp-header {
    position:sticky; top:0; z-index:50;
    background:#ffffff;
    border-bottom:1px solid #e2e8f0;
    box-shadow:0 2px 12px rgba(0,0,0,.07);
}
.mph-row {
    display:flex; align-items:center; gap:20px;
    padding:16px 24px 12px; flex-wrap:wrap;
}
.mph-back {
    width:34px; height:34px; border-radius:9px;
    background:#f8fafc; border:1px solid #e2e8f0;
    color:#64748b; display:flex; align-items:center; justify-content:center;
    text-decoration:none; flex-shrink:0; font-size:.95rem; transition:all .15s;
}
.mph-back:hover { background:#f1f5f9; color:#1e293b; border-color:#cbd5e1; }
.mph-info { flex:1; min-width:0; display:flex; flex-direction:column; gap:5px; }
.mph-chips { display:flex; align-items:center; gap:6px; flex-wrap:wrap; }
.mph-code {
    font-family:'JetBrains Mono',monospace;
    font-size:.68rem; font-weight:700;
    padding:3px 9px; border-radius:6px; border:1px solid; letter-spacing:.02em;
}
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
.mph-av {
    width:30px; height:30px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:.56rem; font-weight:700; border:2px solid #fff;
    margin-left:-6px; position:relative; cursor:default; transition:transform .12s;
}
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

/* Barre types */
.mph-typebar { display:flex; gap:0; border-top:1px solid #f1f5f9; overflow-x:auto; padding:0 24px; }
.mph-typebar::-webkit-scrollbar { height:0; }
.mph-typebtn {
    display:inline-flex; align-items:center; gap:7px;
    padding:9px 16px; background:transparent; border:none;
    border-bottom:2px solid transparent;
    color:#64748b; font-size:.72rem; font-weight:500;
    cursor:pointer; white-space:nowrap; transition:all .15s;
    font-family:'Plus Jakarta Sans',sans-serif; position:relative; top:1px;
}
.mph-typebtn:hover { color:#334155; }
.mph-typebtn.active { font-weight:700; }
.mtb-dot  { width:7px; height:7px; border-radius:50%; flex-shrink:0; }
.mtb-cnt  { background:#f1f5f9; color:#94a3b8; font-size:.62rem; padding:0 6px; border-radius:8px; }
.mtb-done { font-size:.62rem; font-weight:700; }
.mtb-unread {
    background:#dc2626; color:#fff;
    font-size:.54rem; font-weight:700;
    padding:1px 5px; border-radius:10px;
    min-width:16px; text-align:center;
}

/* ═══ BODY ═══ */
.mp-body { display:flex; flex:1; overflow:hidden; }

/* Sidebar */
.mp-sidebar {
    width:220px; flex-shrink:0;
    background:#ffffff; border-right:1px solid #e2e8f0;
    display:flex; flex-direction:column; overflow-y:auto;
}
.mp-sidebar::-webkit-scrollbar { width:3px; }
.mp-sidebar::-webkit-scrollbar-thumb { background:#e2e8f0; border-radius:3px; }
.mps-block { display:flex; flex-direction:column; }
.mps-team-block { border-top:1px solid #f1f5f9; flex:1; }
.mps-label {
    display:flex; align-items:center; gap:6px; padding:12px 14px 6px;
    font-size:.6rem; font-weight:700; text-transform:uppercase; letter-spacing:.1em; color:#94a3b8;
}
.mps-badge { margin-left:auto; background:#f1f5f9; color:#64748b; font-size:.58rem; padding:1px 6px; border-radius:8px; font-weight:600; }
.mps-list  { display:flex; flex-direction:column; gap:1px; padding:2px 8px 10px; }
.mps-item  {
    display:flex; align-items:flex-start; gap:9px;
    padding:8px 10px; border-radius:8px;
    background:transparent; border:none; color:#64748b;
    cursor:pointer; text-align:left; transition:all .12s;
    width:100%; font-family:'Plus Jakarta Sans',sans-serif; font-size:.73rem;
}
.mps-item:hover { background:#f8fafc; color:#334155; }
.mps-item.on    { background:#f1f5f9; color:#1e293b; font-weight:600; }
.mps-dot       { width:7px; height:7px; border-radius:50%; flex-shrink:0; margin-top:4px; }
.mps-item-txt  { display:flex; flex-direction:column; gap:2px; min-width:0; }
.mps-item-name { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.mps-item-date { font-size:.62rem; color:#94a3b8; }
.mps-team      { display:flex; flex-direction:column; gap:2px; padding:4px 8px 12px; }
.mps-member    { display:flex; align-items:center; gap:9px; padding:7px 8px; border-radius:8px; }
.mps-me        { background:#f8fafc; }
.mps-av        { width:28px; height:28px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:.54rem; font-weight:700; flex-shrink:0; }
.mps-minfo     { display:flex; flex-direction:column; gap:3px; min-width:0; }
.mps-mname     { font-size:.7rem; font-weight:600; color:#334155; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.mps-me-tag    { font-size:.5rem; background:#0891b2; color:#fff; padding:0 4px; border-radius:3px; margin-left:4px; font-weight:700; }
.mps-mrole     { font-size:.58rem; font-weight:600; padding:1px 5px; border-radius:4px; display:inline-block; width:fit-content; }

/* Main */
.mp-main { flex:1; overflow-y:auto; background:#f1f5f9; }
.mp-main::-webkit-scrollbar { width:4px; }
.mp-main::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:4px; }
.mp-empty { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:12px; padding:80px 20px; color:#94a3b8; }
.mp-empty i { font-size:2.5rem; }
.mp-empty p { font-size:.9rem; font-weight:600; color:#cbd5e1; }

/* Section mois */
.tg-section { padding-bottom:8px; }
.tg-sticky {
    position:sticky; top:0; z-index:30;
    background:rgba(241,245,249,.97); backdrop-filter:blur(10px);
    border-bottom:1px solid #e2e8f0;
}
.tg-sticky-inner { display:flex; align-items:center; gap:14px; padding:9px 28px; }
.tg-date-pill {
    display:inline-flex; align-items:center; gap:7px;
    background:#fff; border:1px solid #e2e8f0;
    padding:5px 12px; border-radius:20px; flex-shrink:0;
    box-shadow:0 1px 4px rgba(0,0,0,.05);
}
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

/* Phase row */
.ph-row { display:flex; gap:0; align-items:flex-start; }
.ph-row-disabled { opacity:.5; }
.ph-tl   { display:flex; flex-direction:column; align-items:center; width:32px; flex-shrink:0; padding-top:20px; }
.ph-tl-dot  { width:11px; height:11px; border-radius:50%; flex-shrink:0; z-index:1; }
.ph-tl-line { width:1px; background:#e2e8f0; flex:1; min-height:24px; margin-top:4px; }
.ph-row:last-child .ph-tl-line { display:none; }

/* Phase card */
.ph-card {
    flex:1; min-width:0; margin-bottom:18px;
    border-radius:12px; border:1px solid #e2e8f0;
    background:#ffffff; box-shadow:0 1px 4px rgba(0,0,0,.04);
    overflow:hidden; transition:box-shadow .15s, border-color .15s;
}
.ph-card:hover { box-shadow:0 4px 16px rgba(0,0,0,.08); border-color:#cbd5e1; }
.phc-pending    { border-color:#c7d2fe !important; background:#fafbff !important; }
.phc-inprogress { border-color:#fcd34d !important; background:#fffef5 !important; }
.phc-done       { border-color:#bbf7d0 !important; background:#f0fdf4 !important; }
.phc-disabled   { opacity:.45; pointer-events:none; }
.phc-validated  { border-color:#bbf7d0 !important; }

/* En-tête card */
.phc-head {
    display:flex; align-items:center; justify-content:space-between;
    gap:10px; padding:12px 16px 10px;
    border-bottom:1px solid #f8fafc; background:#fafbfc;
}
.phc-head-l { display:flex; align-items:center; gap:8px; flex-wrap:wrap; min-width:0; }
.phc-head-r { display:flex; align-items:center; gap:6px; flex-shrink:0; flex-wrap:wrap; }
.phc-type-chip { font-size:.6rem; font-weight:700; padding:2px 9px; border-radius:20px; border:1px solid; letter-spacing:.03em; white-space:nowrap; }
.phc-code { font-family:'JetBrains Mono',monospace; font-size:.65rem; font-weight:700; color:#475569; letter-spacing:.02em; }
.phc-ent  { display:inline-flex; align-items:center; gap:3px; font-size:.64rem; color:#94a3b8; }
.phc-valst { display:inline-flex; align-items:center; gap:4px; font-size:.6rem; font-weight:700; padding:2px 8px; border-radius:8px; text-transform:uppercase; letter-spacing:.05em; }
.valst-submitted { background:#fef9c3; color:#a16207; }
.valst-validated { background:#dcfce7; color:#16a34a; }
.valst-rejected  { background:#fee2e2; color:#dc2626; }
.valst-in_review { background:#ede9fe; color:#7c3aed; }
.phc-st { font-size:.6rem; font-weight:700; padding:3px 9px; border-radius:8px; text-transform:uppercase; letter-spacing:.05em; white-space:nowrap; }
.phst-pending     { background:#f1f5f9; color:#64748b; }
.phst-in_progress { background:#fef9c3; color:#a16207; }
.phst-completed   { background:#dcfce7; color:#16a34a; }
.phst-skipped     { background:#f1f5f9; color:#94a3b8; }
.phc-pct { font-size:.68rem; font-weight:700; }

/* Boutons actions */
.phc-btn {
    height:28px; padding:0 10px; border-radius:7px;
    display:inline-flex; align-items:center; justify-content:center; gap:5px;
    font-size:.7rem; cursor:pointer; border:none; transition:all .12s;
    flex-shrink:0; text-decoration:none;
    font-family:'Plus Jakarta Sans',sans-serif; font-weight:600; white-space:nowrap;
}
.phc-btn:disabled { opacity:.3; cursor:not-allowed; }
.phb-start    { background:#dcfce7; color:#16a34a; border:1px solid #bbf7d0; }
.phb-start:hover { background:#16a34a; color:#fff; }
.phb-form     { background:#eff6ff; color:#2563eb; border:1px solid #bfdbfe; }
.phb-form:hover { background:#2563eb; color:#fff; }
.phb-form-locked { background:#dcfce7 !important; color:#16a34a !important; border-color:#bbf7d0 !important; }
.phb-form-review { background:#ede9fe !important; color:#7c3aed !important; border-color:#ddd6fe !important; }
.phb-form-lbl { font-size:.66rem; }
.phb-chat     { background:#faf5ff; color:#7c3aed; border:1px solid #e9d5ff; position:relative; width:28px; padding:0; }
.phb-chat:hover { background:#7c3aed; color:#fff; }
.phb-badge {
    position:absolute; top:-5px; right:-5px;
    width:15px; height:15px; background:#dc2626;
    border-radius:50%; font-size:.42rem; font-weight:700;
    color:#fff; display:flex; align-items:center; justify-content:center;
    border:1.5px solid #fff;
}
.phb-badge-read { background:#94a3b8 !important; }
.phbn-unread { border-color:#e9d5ff !important; }
.phb-disable { background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0; width:28px; padding:0; }
.phb-enable  { background:#fef9c3; color:#a16207; border:1px solid #fde68a; width:28px; padding:0; }
.phb-note    { background:#f8fafc; color:#94a3b8; border:1px solid #e2e8f0; width:28px; padding:0; }
.phbn-on     { background:#fef9c3 !important; color:#a16207 !important; border-color:#fde68a !important; }
.phb-validate { background:#dcfce7; color:#16a34a; border:1px solid #bbf7d0; width:28px; padding:0; }
.phb-validate:hover { background:#16a34a; color:#fff; }

/* Corps */
.phc-body { padding:14px 16px 16px; display:flex; flex-direction:column; gap:12px; }
.phc-title     { font-size:.95rem; font-weight:700; color:#1e293b; line-height:1.4; letter-spacing:-.01em; }
.phc-title-off { color:#94a3b8; text-decoration:line-through; }
.phc-desc      { font-size:.72rem; color:#64748b; line-height:1.5; margin-top:2px; }
.phc-dates-wrap { display:flex; flex-direction:column; gap:8px; }
.phc-dates { display:flex; flex-wrap:wrap; gap:14px; }
.phcd { display:inline-flex; align-items:center; gap:5px; font-size:.72rem; color:#475569; font-family:'JetBrains Mono',monospace; }
.phcd em { font-style:normal; font-size:.6rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; font-family:'Plus Jakarta Sans',sans-serif; color:#94a3b8; }
.phcd strong { color:#334155; font-weight:500; }
.phcd-arr { font-size:.65rem; color:#cbd5e1; }
.phcd-real strong { color:#16a34a; }
.phcd-dur { font-size:.66rem; font-weight:700; background:#f1f5f9; color:#64748b; padding:2px 8px; border-radius:8px; align-self:center; }
.phc-bar  { height:4px; background:#f1f5f9; border-radius:4px; overflow:hidden; }
.phc-bar-fill { height:100%; border-radius:4px; transition:width .5s ease; }
@keyframes bar-pulse { 0%,100%{opacity:1} 50%{opacity:.6} }
.phc-bar-anim { animation:bar-pulse 2s ease infinite; }

/* Auditeurs */
.phc-auds-row {
    display:flex; align-items:center; gap:10px; flex-wrap:wrap;
    padding:10px 12px; border-radius:9px; background:#f8fafc; border:1px solid #f1f5f9;
}
.phc-todo-badge {
    display:inline-flex; align-items:center; gap:5px;
    font-size:.64rem; font-weight:700; color:#334155;
    background:#f1f5f9; border:1px solid #e2e8f0;
    padding:4px 11px; border-radius:20px;
    letter-spacing:.03em; text-transform:uppercase; flex-shrink:0;
}
.phc-readonly-badge {
    display:inline-flex; align-items:center; gap:4px;
    font-size:.6rem; font-weight:600; padding:2px 8px; border-radius:8px;
    background:#f8fafc; color:#94a3b8; border:1px solid #e2e8f0;
}
.phc-auds { display:flex; flex-wrap:wrap; gap:6px; }
.phc-aud  {
    display:inline-flex; align-items:center; gap:5px;
    padding:4px 10px 4px 4px; border-radius:20px;
    border:1px solid #e2e8f0; font-size:.68rem;
    cursor:default; position:relative; background:#fff;
}
.phc-aud-me { border-color:#bae6fd !important; background:#f0f9ff !important; }
.phca-av   { width:22px; height:22px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:.52rem; font-weight:700; }
.phca-name { font-weight:600; color:#334155; }
.phca-role { font-size:.56rem; font-weight:600; opacity:.8; padding:1px 4px; border-radius:4px; }
.phca-me-dot { position:absolute; top:1px; right:1px; width:6px; height:6px; background:#0891b2; border-radius:50%; border:1px solid #fff; }

/* CTA Formulaire */
.phc-form-cta {
    display:flex; align-items:center; justify-content:space-between; gap:12px;
    padding:12px 14px; border-radius:10px; border:1px solid; background:#fafbfc;
    transition:box-shadow .15s;
}
.phc-form-cta:hover { box-shadow:0 2px 10px rgba(0,0,0,.06); }
.phcf-left  { display:flex; align-items:center; gap:11px; min-width:0; }
.phcf-icon  { width:36px; height:36px; border-radius:9px; display:flex; align-items:center; justify-content:center; font-size:1rem; flex-shrink:0; }
.phcf-info  { display:flex; flex-direction:column; gap:3px; }
.phcf-label { font-size:.78rem; font-weight:700; color:#1e293b; }
.phcf-status { font-size:.65rem; font-weight:600; }
.phcfs-draft     { color:#94a3b8; }
.phcfs-in_review { color:#7c3aed; }
.phcfs-validated { color:#16a34a; }
.phcfs-rejected  { color:#dc2626; }
.phcf-btn {
    display:inline-flex; align-items:center; gap:6px;
    padding:8px 16px; border-radius:8px; font-size:.76rem; font-weight:700;
    text-decoration:none; flex-shrink:0; transition:opacity .12s, transform .1s;
    box-shadow:0 2px 8px rgba(0,0,0,.12); font-family:'Plus Jakarta Sans',sans-serif;
}
.phcf-btn:hover { opacity:.9; transform:translateY(-1px); }

/* Marquages */
.phc-mks { display:flex; flex-direction:column; gap:4px; padding:10px 16px; border-top:1px solid #f1f5f9; background:#fafbfc; }
.phc-mk { display:flex; gap:10px; padding:8px 10px; border-radius:8px; background:#fff; border:1px solid #f1f5f9; }
.phc-mk-mine { background:#eff6ff !important; border-color:#bfdbfe !important; }
.phcmk-av   { width:24px; height:24px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:.52rem; font-weight:700; flex-shrink:0; }
.phcmk-body { flex:1; min-width:0; }
.phcmk-meta { display:flex; align-items:center; gap:6px; flex-wrap:wrap; margin-bottom:4px; }
.phcmk-who  { font-size:.7rem; font-weight:600; }
.phcmk-r    { font-size:.58rem; color:#64748b; }
.phcmk-date { font-size:.58rem; color:#94a3b8; margin-left:auto; }
.phcmk-txt  { font-size:.73rem; color:#475569; line-height:1.5; }
.phcmk-st   { font-size:.58rem; font-weight:600; padding:1px 6px; border-radius:6px; }
.mkst-draft     { background:#f1f5f9; color:#64748b; }
.mkst-submitted { background:#eff6ff; color:#2563eb; }
.mkst-validated { background:#dcfce7; color:#16a34a; }
.mkst-rejected  { background:#fee2e2; color:#dc2626; }

/* Aperçu chat */
.phc-chat-prev {
    border-top:1px solid #f1f5f9; padding:10px 16px 12px;
    background:#fdfaff; display:flex; flex-direction:column; gap:6px;
}
.phcp-hd  { display:flex; align-items:center; gap:7px; font-size:.64rem; color:#94a3b8; margin-bottom:2px; }
.phcp-unread-badge {
    background:#dc2626; color:#fff;
    font-size:.56rem; font-weight:700;
    padding:1px 7px; border-radius:10px;
}
.phcp-all { margin-left:auto; font-size:.6rem; color:#7c3aed; background:none; border:none; cursor:pointer; font-family:'Plus Jakarta Sans',sans-serif; font-weight:600; }
.phcp-all:hover { text-decoration:underline; }
.phcp-msg { display:flex; gap:8px; padding:6px 8px; border-radius:7px; background:#fff; border:1px solid #f1f5f9; }
.phcp-msg-unread { border-color:#ddd6fe !important; background:#faf5ff !important; }
.phcpm-av  { width:20px; height:20px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:.46rem; font-weight:700; flex-shrink:0; }
.phcpm-body { flex:1; min-width:0; }
.phcpm-meta { display:flex; align-items:center; gap:5px; margin-bottom:3px; flex-wrap:wrap; }
.phcpm-meta span { font-size:.58rem; }
.phcpm-who  { font-weight:600; color:#334155; }
.phcpm-pri.pri-urgent   { color:#a16207; font-weight:700; }
.phcpm-pri.pri-bloquant { color:#dc2626; font-weight:700; }
.phcpm-type { color:#7c3aed; }
.phcpm-date { color:#94a3b8; margin-left:auto; }
.phcpm-txt  { font-size:.71rem; color:#64748b; line-height:1.4; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.chat-bloquant { border-color:#fecaca !important; }
.chat-urgent   { border-color:#fde68a !important; }

/* ══ PANEL CHAT ══ */
.chat-overlay { position:fixed; inset:0; background:rgba(0,0,0,.25); z-index:200; }
.chat-panel {
    position:fixed; top:0; right:0; bottom:0; width:420px; max-width:95vw;
    background:#fff; border-left:1px solid #e2e8f0;
    z-index:201; display:flex; flex-direction:column;
    box-shadow:-8px 0 40px rgba(0,0,0,.12);
}
.slide-right-enter-active,.slide-right-leave-active { transition:transform .25s ease; }
.slide-right-enter-from,.slide-right-leave-to { transform:translateX(100%); }
.chat-hd {
    display:flex; align-items:center; justify-content:space-between;
    gap:12px; padding:16px 18px 12px; border-bottom:1px solid #f1f5f9;
    background:#fafbfc; flex-shrink:0;
}
.chat-hd-info { display:flex; align-items:center; gap:10px; min-width:0; flex:1; }
.chat-hd-info i { font-size:1.1rem; flex-shrink:0; }
.chat-hd-label { display:block; font-size:.82rem; font-weight:700; color:#1e293b; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:220px; }
.chat-hd-form  { display:block; font-size:.62rem; color:#94a3b8; font-family:'JetBrains Mono',monospace; }
.chat-hd-badges { display:flex; align-items:center; gap:6px; flex-shrink:0; }
.chat-hd-count  { font-size:.62rem; color:#94a3b8; background:#f1f5f9; padding:2px 8px; border-radius:8px; }
.chat-close-btn {
    width:28px; height:28px; border-radius:7px; background:#f8fafc;
    border:1px solid #e2e8f0; color:#64748b;
    display:flex; align-items:center; justify-content:center;
    cursor:pointer; font-size:.75rem; flex-shrink:0;
}
.chat-close-btn:hover { color:#1e293b; }

/* Membres dans le chat */
.chat-members {
    display:flex; align-items:center; gap:10px;
    padding:8px 16px; background:#f8fafc; border-bottom:1px solid #f1f5f9;
    flex-shrink:0;
}
.chat-members-label { font-size:.6rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:#94a3b8; display:flex; align-items:center; gap:5px; flex-shrink:0; }
.chat-members-avs   { display:flex; gap:-4px; }
.chat-mbr-av {
    width:24px; height:24px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:.5rem; font-weight:700;
    border:2px solid #f8fafc;
    margin-left:-5px; cursor:default; position:relative;
    transition:transform .1s;
}
.chat-mbr-av:first-child { margin-left:0; }
.chat-mbr-av:hover { transform:scale(1.2); z-index:2; }
.chat-mbr-me { position:absolute; bottom:-1px; right:-1px; width:7px; height:7px; background:#0891b2; border-radius:50%; border:1.5px solid #f8fafc; }

/* Messages */
.chat-msgs { flex:1; overflow-y:auto; padding:12px 14px; display:flex; flex-direction:column; gap:2px; }
.chat-msgs::-webkit-scrollbar { width:3px; }
.chat-msgs::-webkit-scrollbar-thumb { background:#e2e8f0; }
.chat-empty { display:flex; flex-direction:column; align-items:center; gap:8px; padding:40px 20px; color:#cbd5e1; text-align:center; }
.chat-empty i { font-size:2rem; }
.chat-empty p { font-size:.82rem; color:#94a3b8; font-weight:600; }
.chat-empty span { font-size:.72rem; color:#cbd5e1; }

/* Séparateur de jour */
.chat-day-sep {
    display:flex; align-items:center; gap:10px;
    margin:12px 0 6px;
}
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
.cmsg-txt {
    font-size:.77rem; color:#475569; line-height:1.55;
    background:#f8fafc; padding:8px 11px; border-radius:9px;
    border:1px solid #f1f5f9; word-break:break-word;
}
.chat-mine .cmsg-txt { background:#eff6ff; border-color:#bfdbfe; color:#1e40af; border-radius:9px 2px 9px 9px; }
.cmsg-actions { display:flex; align-items:center; gap:8px; margin-top:4px; }
.cmsg-reply { background:none; border:none; color:#94a3b8; font-size:.62rem; cursor:pointer; font-family:'Plus Jakarta Sans',sans-serif; display:flex; align-items:center; gap:3px; }
.cmsg-reply:hover { color:#7c3aed; }
.cmsg-thread { font-size:.58rem; color:#94a3b8; display:flex; align-items:center; gap:3px; font-style:italic; }

/* Compose */
.chat-compose { border-top:1px solid #f1f5f9; padding:12px 14px 14px; background:#fafbfc; flex-shrink:0; display:flex; flex-direction:column; gap:8px; }
.chat-reply-preview {
    display:flex; align-items:center; gap:7px; font-size:.68rem; color:#7c3aed;
    background:#faf5ff; border:1px solid #ddd6fe; padding:5px 10px; border-radius:7px;
    overflow:hidden;
}
.chat-reply-preview span { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; flex:1; }
.chat-reply-preview button { margin-left:auto; background:none; border:none; color:#94a3b8; cursor:pointer; flex-shrink:0; }
.chat-opts { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.chat-select { background:#fff; border:1px solid #e2e8f0; color:#334155; border-radius:7px; padding:4px 9px; font-size:.68rem; font-family:'Plus Jakarta Sans',sans-serif; cursor:pointer; outline:none; }
.chat-prios  { display:flex; gap:4px; }
.chat-prio-btn {
    display:inline-flex; align-items:center; gap:4px;
    padding:3px 10px; border-radius:20px; border:1px solid #e2e8f0;
    background:#f8fafc; color:#64748b; font-size:.62rem; font-weight:600;
    cursor:pointer; transition:all .12s; font-family:'Plus Jakarta Sans',sans-serif;
}
.chat-prio-btn.active.cpb-normal   { background:#eff6ff; color:#2563eb; border-color:#bfdbfe; }
.chat-prio-btn.active.cpb-urgent   { background:#fef9c3; color:#a16207; border-color:#fde68a; }
.chat-prio-btn.active.cpb-bloquant { background:#fee2e2; color:#dc2626; border-color:#fecaca; }
.chat-input-row { display:flex; gap:8px; align-items:flex-end; }
.chat-ta {
    flex:1; background:#fff; border:1px solid #e2e8f0; border-radius:9px;
    color:#1e293b; font-family:'Plus Jakarta Sans',sans-serif; font-size:.8rem;
    padding:9px 12px; resize:none; outline:none; transition:border-color .12s;
}
.chat-ta:focus { border-color:#a5b4fc; }
.chat-ta::placeholder { color:#cbd5e1; }
.chat-send {
    width:36px; height:36px; border-radius:9px; border:none; color:#fff;
    cursor:pointer; display:flex; align-items:center; justify-content:center;
    font-size:.85rem; flex-shrink:0; transition:opacity .12s;
}
.chat-send:disabled { opacity:.4; cursor:not-allowed; }
.chat-hint { font-size:.58rem; color:#cbd5e1; }

@keyframes spin { to { transform:rotate(360deg); } }
.spin { animation:spin .7s linear infinite; display:inline-block; }

/* ══ MODALES ══ */
.m-bg {
    position:fixed; inset:0;
    background:rgba(15,23,42,.55); backdrop-filter:blur(8px);
    z-index:1000; display:flex; align-items:center; justify-content:center;
}
.mfade-enter-active,.mfade-leave-active { transition:opacity .18s; }
.mfade-enter-from,.mfade-leave-to { opacity:0; }
.m-box {
    background:#fff; border:1px solid #e2e8f0; border-radius:16px;
    width:440px; max-width:95vw;
    box-shadow:0 24px 60px rgba(0,0,0,.18);
    display:flex; flex-direction:column; overflow:hidden;
}
.m-box-lg { width:520px; }
.m-hd {
    display:flex; align-items:center; gap:8px; padding:16px 18px;
    font-size:.84rem; font-weight:700; color:#1e293b;
    border-bottom:1px solid #f1f5f9; background:#fafbfc;
}
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
.val-warn { display:flex; align-items:center; gap:8px; padding:10px 12px; background:#fef9c3; border:1px solid #fde68a; border-radius:9px; font-size:.75rem; color:#92400e; }
.val-choice { display:flex; gap:8px; }
.val-btn { flex:1; display:flex; align-items:center; justify-content:center; gap:6px; padding:9px 14px; border-radius:9px; border:1px solid #e2e8f0; font-size:.78rem; font-weight:600; cursor:pointer; font-family:'Plus Jakarta Sans',sans-serif; background:#f8fafc; color:#64748b; transition:all .15s; }
.val-reject.active { background:#fee2e2; border-color:#fecaca; color:#dc2626; }
.val-ok.active     { background:#dcfce7; border-color:#bbf7d0; color:#16a34a; }
.val-note-wrap { display:flex; flex-direction:column; gap:6px; }
.val-note-lbl { font-size:.62rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:#94a3b8; }
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

/* ══ RÔLES ══ */
.rb-DM { background:#fef9c3; color:#a16207; border:1px solid #fde68a; }
.rb-CM { background:#eff6ff; color:#2563eb; border:1px solid #bfdbfe; }
.rb-AS { background:#dcfce7; color:#16a34a; border:1px solid #bbf7d0; }
.rb-AJ { background:#faf5ff; color:#7c3aed; border:1px solid #ddd6fe; }
.av-DM { background:#fef9c3; color:#a16207; }
.av-CM { background:#eff6ff; color:#2563eb; }
.av-AS { background:#dcfce7; color:#16a34a; }
.av-AJ { background:#faf5ff; color:#7c3aed; }

/* ══ TOAST ══ */
.mp-toast {
    position:fixed; bottom:24px; right:24px; z-index:9999;
    display:flex; align-items:center; gap:9px;
    padding:12px 18px; border-radius:11px;
    font-size:.78rem; font-weight:600;
    box-shadow:0 12px 44px rgba(0,0,0,.15);
    font-family:'Plus Jakarta Sans',sans-serif;
    max-width:380px; border:1px solid;
}
.mp-toast button { background:none; border:none; color:inherit; cursor:pointer; margin-left:5px; opacity:.6; }
.t-success { background:#f0fdf4; color:#16a34a; border-color:#bbf7d0; }
.t-warning { background:#fefce8; color:#a16207; border-color:#fde68a; }
.t-error   { background:#fff1f2; color:#dc2626; border-color:#fecaca; }
.toast-enter-active,.toast-leave-active { transition:all .22s; }
.toast-enter-from,.toast-leave-to { opacity:0; transform:translateY(10px); }

@media (max-width:900px) {
    .mp-body { flex-direction:column; }
    .mp-sidebar { width:100%; max-height:160px; flex-direction:row; overflow-x:auto; }
    .mps-team-block { display:none; }
    .mph-ring { display:none; }
    .tg-phases { padding:12px; }
    .ph-tl { display:none; }
    .chat-panel { width:100%; }
}
</style>