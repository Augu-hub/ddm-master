<template>
    <VerticalLayout>
        <div class="ad-shell">

            <!-- ══════════════════ HERO ══════════════════ -->
            <div class="ad-hero">
                <div class="hero-mesh"></div>
                <div class="hero-inner">
                    <!-- Identité -->
                    <div class="hero-id">
                        <div class="hero-avatar">
                            <img v-if="auditor?.avatar" :src="`/storage/${auditor.avatar}`" :alt="auditor?.nom_complet" />
                            <span v-else class="ha-initials">{{ auditor?.initiales }}</span>
                            <span class="ha-dot" :class="auditor?.status === 'active' ? 'ha-on' : 'ha-off'"></span>
                        </div>
                        <div class="hero-who">
                            <p class="hw-kicker">Espace Auditeur · {{ currentYear }}</p>
                            <h1 class="hw-name">{{ auditor?.nom_complet }}</h1>
                            <div class="hw-meta">
                                <span class="hw-pill hw-code"><i class="ti ti-fingerprint"></i>{{ auditor?.audit_code }}</span>
                                <span v-if="auditor?.entity" class="hw-pill"><i class="ti ti-building"></i>{{ auditor.entity }}</span>
                                <span v-if="auditor?.audit_experience" class="hw-pill"><i class="ti ti-award"></i>{{ auditor.audit_experience }} an(s) d'audit</span>
                            </div>
                        </div>
                        <button class="hero-logout" title="Déconnexion" @click="handleLogout">
                            <i class="ti ti-logout"></i>
                        </button>
                    </div>

                    <!-- KPIs -->
                    <div class="hero-kpis">
                        <div v-for="k in kpis" :key="k.key" class="kpi" :class="`kpi-${k.key}`">
                            <i :class="k.icon"></i>
                            <div class="kpi-txt">
                                <span class="kpi-v">{{ k.value }}</span>
                                <span class="kpi-l">{{ k.label }}</span>
                            </div>
                        </div>
                        <div class="kpi kpi-taux">
                            <svg viewBox="0 0 36 36" class="kpi-ring">
                                <circle cx="18" cy="18" r="15" fill="none" stroke="rgba(255,255,255,.08)" stroke-width="3"/>
                                <circle cx="18" cy="18" r="15" fill="none" stroke="#34d399" stroke-width="3"
                                    stroke-linecap="round" stroke-dashoffset="25"
                                    :stroke-dasharray="`${stats.taux_realisation ?? 0} ${100 - (stats.taux_realisation ?? 0)}`"/>
                            </svg>
                            <div class="kpi-txt">
                                <span class="kpi-v">{{ stats.taux_realisation ?? 0 }}%</span>
                                <span class="kpi-l">Réalisation</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alerte missions démarrables -->
                <div v-if="missionsDemarrables.length" class="hero-alert" @click="activeTab = 'missions'">
                    <i class="ti ti-player-play"></i>
                    <strong>{{ missionsDemarrables.length }} mission(s) prête(s) à démarrer</strong>
                    <span class="ha-cta">Voir <i class="ti ti-arrow-right"></i></span>
                </div>
            </div>

            <!-- ══════════════════ BANDEAU MISSION ACTIVE ══════════════════ -->
            <div v-if="missionEnCours || prochaineMission" class="ad-focus">
                <div v-if="missionEnCours" class="focus-card focus-current">
                    <div class="fc-head">
                        <span class="fc-tag fc-tag-run"><i class="ti ti-player-play"></i> Mission en cours</span>
                        <code class="fc-code">{{ missionEnCours.code_mission }}</code>
                    </div>
                    <h3 class="fc-title">{{ missionEnCours.libelle }}</h3>
                    <div class="fc-meta">
                        <span><i class="ti ti-calendar"></i>{{ missionEnCours.date_debut_fr }} → {{ missionEnCours.date_fin_fr }}</span>
                        <span v-if="missionEnCours.entities_list"><i class="ti ti-building"></i>{{ missionEnCours.entities_list }}</span>
                        <span class="fc-role" :class="`rb-${missionEnCours.mon_role}`">{{ missionEnCours.mon_role }}</span>
                    </div>
                    <div class="fc-prog">
                        <div class="fc-bar"><div class="fc-fill" :style="`width:${missionEnCours.progression}%`"></div></div>
                        <span class="fc-pct">{{ missionEnCours.progression }}%</span>
                    </div>
                    <a :href="phasesUrl(missionEnCours.mission_id)" class="fc-btn">
                        Continuer la mission <i class="ti ti-arrow-right"></i>
                    </a>
                </div>

                <div v-if="prochaineMission" class="focus-card focus-next">
                    <div class="fc-head">
                        <span class="fc-tag fc-tag-next"><i class="ti ti-calendar-clock"></i> Prochaine mission</span>
                        <code class="fc-code">{{ prochaineMission.code_mission }}</code>
                    </div>
                    <h3 class="fc-title">{{ prochaineMission.libelle }}</h3>
                    <div class="fc-meta">
                        <span><i class="ti ti-calendar"></i>{{ prochaineMission.date_debut_fr }} → {{ prochaineMission.date_fin_fr }}</span>
                        <span><i class="ti ti-clock"></i>{{ prochaineMission.duree }} jours</span>
                        <span class="fc-role" :class="`rb-${prochaineMission.mon_role}`">{{ prochaineMission.mon_role }}</span>
                    </div>
                    <button v-if="canStart(prochaineMission)" class="fc-btn fc-btn-start" @click="confirmStartMission(prochaineMission)">
                        <i class="ti ti-player-play"></i> Démarrer maintenant
                    </button>
                    <span v-else class="fc-wait"><i class="ti ti-hourglass"></i> Démarre le {{ prochaineMission.date_debut_fr }}</span>
                </div>
            </div>

            <!-- ══════════════════ ONGLETS ══════════════════ -->
            <div class="ad-tabs">
                <button v-for="t in tabs" :key="t.id" class="ad-tab"
                    :class="{ on: activeTab === t.id }" @click="activeTab = t.id">
                    <i :class="`ti ti-${t.icon}`"></i>
                    {{ t.label }}
                    <span v-if="t.badge" class="ad-tab-badge">{{ t.badge }}</span>
                </button>
            </div>

            <div class="ad-body">

                <!-- ════════ VUE D'ENSEMBLE ════════ -->
                <template v-if="activeTab === 'overview'">
                    <div class="ov-grid">
                        <!-- Année en un coup d'œil -->
                        <section class="ad-card ov-year">
                            <div class="adc-label"><i class="ti ti-calendar-stats"></i> Mon année {{ currentYear }}</div>
                            <div class="year-strip">
                                <button v-for="m in calendrier" :key="m.mois"
                                    class="ys-month" :class="[`ys-${m.status}`, { on: selectedMonth === m.mois }]"
                                    @click="selectedMonth = selectedMonth === m.mois ? null : m.mois">
                                    <span class="ys-lbl">{{ m.label }}</span>
                                    <span class="ys-jours">{{ m.jours ? m.jours + 'j' : '—' }}</span>
                                    <span class="ys-n" v-if="m.nb_missions">{{ m.nb_missions }}</span>
                                </button>
                            </div>
                            <div class="year-legend">
                                <span><i class="lg lg-en_cours"></i>En cours</span>
                                <span><i class="lg lg-planifiee"></i>Planifiée</span>
                                <span><i class="lg lg-terminee"></i>Terminée</span>
                                <span><i class="lg lg-libre"></i>Libre</span>
                            </div>
                            <!-- Détail du mois sélectionné -->
                            <div v-if="selectedMonthData" class="ym-detail">
                                <div class="ymd-head">
                                    <strong>{{ selectedMonthData.label_long }} {{ currentYear }}</strong>
                                    <span>{{ selectedMonthData.nb_missions }} mission(s) · {{ selectedMonthData.jours }}j · {{ selectedMonthData.semaines }} sem.</span>
                                </div>
                                <div v-for="it in selectedMonthData.missions" :key="it.id + '-' + (it.entity_id ?? 0)" class="ymd-item">
                                    <code>{{ it.code_mission }}</code>
                                    <span class="ymd-lib">{{ it.libelle }}</span>
                                    <span v-if="it.entity_name" class="ymd-ent"><i class="ti ti-building"></i>{{ it.entity_name }}</span>
                                    <span class="ymd-dates">{{ it.date_debut_fr }} → {{ it.date_fin_fr }}</span>
                                    <span class="ymd-j">{{ it.jours_dans_mois }}j</span>
                                    <span class="mc-status" :class="`mcs-${it.status}`">{{ statusLabel(it.status) }}</span>
                                </div>
                                <p v-if="!selectedMonthData.missions.length" class="ymd-empty">Aucune mission ce mois — période libre.</p>
                            </div>
                        </section>

                        <!-- Répartition -->
                        <section class="ad-card ov-side">
                            <div class="adc-label"><i class="ti ti-chart-donut"></i> Répartition</div>
                            <div class="rep-rows">
                                <div class="rep-row">
                                    <span class="rep-lbl"><i class="lg lg-en_cours"></i>En cours</span>
                                    <div class="rep-bar"><div class="rep-fill rf-en_cours" :style="`width:${pct(stats.en_cours)}%`"></div></div>
                                    <strong>{{ stats.en_cours ?? 0 }}</strong>
                                </div>
                                <div class="rep-row">
                                    <span class="rep-lbl"><i class="lg lg-planifiee"></i>Planifiées</span>
                                    <div class="rep-bar"><div class="rep-fill rf-planifiee" :style="`width:${pct(stats.planifiees)}%`"></div></div>
                                    <strong>{{ stats.planifiees ?? 0 }}</strong>
                                </div>
                                <div class="rep-row">
                                    <span class="rep-lbl"><i class="lg lg-terminee"></i>Terminées</span>
                                    <div class="rep-bar"><div class="rep-fill rf-terminee" :style="`width:${pct(stats.terminees)}%`"></div></div>
                                    <strong>{{ stats.terminees ?? 0 }}</strong>
                                </div>
                                <div class="rep-row" v-if="stats.annulees">
                                    <span class="rep-lbl"><i class="lg lg-annulee"></i>Annulées</span>
                                    <div class="rep-bar"><div class="rep-fill rf-annulee" :style="`width:${pct(stats.annulees)}%`"></div></div>
                                    <strong>{{ stats.annulees }}</strong>
                                </div>
                            </div>
                            <div class="rep-tot">
                                <div><span>Jours-audit</span><strong>{{ stats.jours_total ?? 0 }}j</strong></div>
                                <div><span>Budget total</span><strong class="rep-money">{{ formatMontantCourt(stats.budget_total ?? 0) }} FCFA</strong></div>
                                <div v-if="stats.nb_risques_total"><span>Risques identifiés</span><strong class="rep-risk">{{ stats.nb_risques_total }} ⚠</strong></div>
                            </div>
                        </section>
                    </div>
                </template>

                <!-- ════════ MES MISSIONS ════════ -->
                <template v-if="activeTab === 'missions'">
                    <div class="ms-toolbar">
                        <div class="ms-search">
                            <i class="ti ti-search"></i>
                            <input v-model="searchMission" placeholder="Rechercher mission, code, entité…" />
                            <button v-if="searchMission" class="ms-clear" @click="searchMission = ''"><i class="ti ti-x"></i></button>
                        </div>
                        <div class="ms-filters">
                            <button v-for="f in statusFilters" :key="f.v" class="ms-f"
                                :class="[{ on: filterStatus === f.v }, `msf-${f.v || 'all'}`]"
                                @click="filterStatus = f.v">
                                {{ f.l }} <span class="ms-f-n">{{ countByStatus(f.v) }}</span>
                            </button>
                        </div>
                    </div>

                    <div v-if="!filteredMissions.length" class="ad-empty">
                        <i class="ti ti-clipboard-off"></i>
                        <p>Aucune mission trouvée</p>
                        <span>Modifiez la recherche ou les filtres</span>
                    </div>

                    <div class="ms-list">
                        <article v-for="aff in filteredMissions" :key="aff.id" class="m-card" :class="`mca-${aff.status}`">
                            <div class="m-stripe" :class="`stripe-${aff.status}`"></div>
                            <div class="m-inner">
                                <!-- head -->
                                <div class="m-head">
                                    <div class="m-head-l">
                                        <code class="m-code">{{ aff.code_mission }}</code>
                                        <span class="mc-status" :class="`mcs-${aff.status}`">{{ statusLabel(aff.status) }}</span>
                                        <span class="m-role" :class="`rb-${aff.mon_role}`"><i class="ti ti-user-circle"></i>{{ aff.mon_role }} — {{ aff.role_libelle }}</span>
                                        <span v-if="canStart(aff)" class="m-start-flag"><i class="ti ti-bolt"></i> Démarre aujourd'hui</span>
                                    </div>
                                    <div class="m-actions">
                                        <button v-if="canStart(aff)" class="m-btn m-btn-start" @click="confirmStartMission(aff)">
                                            <i class="ti ti-player-play"></i> Démarrer
                                        </button>
                                        <a :href="phasesUrl(aff.mission_id)" class="m-btn m-btn-go">
                                            Phases <i class="ti ti-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>

                                <h3 class="m-title">{{ aff.libelle }}</h3>
                                <p v-if="aff.objectif" class="m-obj"><i class="ti ti-target"></i>{{ aff.objectif }}</p>

                                <!-- infos grid -->
                                <div class="m-grid">
                                    <div class="mg-cell">
                                        <span class="mg-l"><i class="ti ti-calendar"></i> Période</span>
                                        <span class="mg-v">{{ aff.date_debut_fr }} → {{ aff.date_fin_fr }}</span>
                                        <span class="mg-sub">{{ aff.duree }} jours</span>
                                    </div>
                                    <div class="mg-cell">
                                        <span class="mg-l"><i class="ti ti-building"></i> Entités</span>
                                        <span class="mg-v mg-trunc">{{ aff.entities_list || '—' }}</span>
                                        <span v-if="aff.processus?.nom" class="mg-sub mg-trunc"><i class="ti ti-route"></i> {{ aff.processus.nom }}</span>
                                    </div>
                                    <div class="mg-cell">
                                        <span class="mg-l"><i class="ti ti-users"></i> Équipe</span>
                                        <div class="mg-team">
                                            <div v-for="mb in teamOf(aff.mission_id).slice(0, 5)" :key="mb.auditeur_id"
                                                class="tm-av" :class="[`av-${mb.role}`, { me: mb.is_me }]"
                                                :title="`${mb.last_name} ${mb.first_name} · ${mb.role_libelle}`">
                                                {{ initials(mb.last_name, mb.first_name) }}
                                            </div>
                                            <span v-if="teamOf(aff.mission_id).length > 5" class="tm-more">+{{ teamOf(aff.mission_id).length - 5 }}</span>
                                            <span v-if="!teamOf(aff.mission_id).length" class="mg-sub">—</span>
                                        </div>
                                    </div>
                                    <div class="mg-cell">
                                        <span class="mg-l"><i class="ti ti-coin"></i> Mon budget</span>
                                        <span class="mg-v mg-money">{{ formatMontantCourt(aff.budget_individuel ?? 0) }} FCFA</span>
                                        <span class="mg-sub">{{ (budgetLignes[aff.id] ?? []).length }} ligne(s)</span>
                                    </div>
                                    <div class="mg-cell">
                                        <span class="mg-l"><i class="ti ti-alert-triangle"></i> Risques</span>
                                        <span class="mg-v" :class="aff.nb_risques ? 'mg-risk' : 'mg-ok'">
                                            {{ aff.nb_risques ? aff.nb_risques + ' identifié(s)' : 'Aucun ✓' }}
                                        </span>
                                        <span v-if="aff.risques?.[0]?.niveau" class="mg-sub">Niveau : {{ aff.risques[0].niveau }}</span>
                                    </div>
                                </div>

                                <!-- progression -->
                                <div class="m-prog">
                                    <div class="mp-bar"><div class="mp-fill" :class="`pf-${aff.status}`" :style="`width:${aff.progression}%`"></div></div>
                                    <span class="mp-pct">{{ aff.progression }}%</span>
                                </div>
                            </div>
                        </article>
                    </div>
                </template>

                <!-- ════════ PLANNING ════════ -->
                <template v-if="activeTab === 'calendrier'">
                    <section class="ad-card">
                        <div class="adc-label"><i class="ti ti-calendar-stats"></i> Planning {{ currentYear }} — charge mensuelle</div>
                        <div class="pl-grid">
                            <div v-for="m in calendrier" :key="m.mois" class="pl-month" :class="`pl-${m.status}`">
                                <div class="plm-head">
                                    <strong>{{ m.label_long }}</strong>
                                    <span class="plm-status" :class="`mcs-${m.status === 'libre' ? 'planifiee' : m.status}`"
                                        :style="m.status === 'libre' ? 'background:rgba(148,163,184,.12);color:#64748b' : ''">
                                        {{ m.status_label }}
                                    </span>
                                </div>
                                <div class="plm-stats">
                                    <span><i class="ti ti-briefcase"></i>{{ m.nb_missions }} mission(s)</span>
                                    <span><i class="ti ti-clock"></i>{{ m.jours }}j · {{ m.semaines }} sem.</span>
                                </div>
                                <div class="plm-charge">
                                    <div class="plm-bar"><div class="plm-fill" :style="`width:${Math.min(m.jours / 22 * 100, 100)}%`"></div></div>
                                </div>
                                <div class="plm-items">
                                    <div v-for="it in m.missions.slice(0, 3)" :key="it.id + '-' + (it.entity_id ?? 0)" class="plm-it"
                                        :title="`${it.libelle} · ${it.date_debut_fr} → ${it.date_fin_fr}`">
                                        <span class="plm-dot" :class="`lg-${it.status}`"></span>
                                        <code>{{ it.code_mission }}</code>
                                        <span class="plm-it-ent">{{ it.entity_name ?? '' }}</span>
                                        <span class="plm-it-j">{{ it.jours_dans_mois }}j</span>
                                    </div>
                                    <span v-if="m.missions.length > 3" class="plm-more">+{{ m.missions.length - 3 }} autre(s)</span>
                                    <span v-if="!m.missions.length" class="plm-free">Période libre</span>
                                </div>
                            </div>
                        </div>
                    </section>
                </template>

                <!-- ════════ BUDGET ════════ -->
                <template v-if="activeTab === 'budget'">
                    <div class="bg-top">
                        <div class="ad-card bg-total">
                            <div class="adc-label"><i class="ti ti-coin"></i> Budget total {{ currentYear }}</div>
                            <div class="bgt-amount">{{ formatMontant(stats.budget_total ?? 0) }} <em>FCFA</em></div>
                            <div class="bgt-sub">{{ affectations.length }} mission(s) · {{ stats.jours_total ?? 0 }} jours-audit</div>
                        </div>
                    </div>
                    <div v-if="!budgetMissions.length" class="ad-empty">
                        <i class="ti ti-coin"></i>
                        <p>Aucun budget individuel affecté</p>
                    </div>
                    <div class="bg-list">
                        <section v-for="aff in budgetMissions" :key="aff.id" class="ad-card bg-card">
                            <div class="bgc-head">
                                <code class="m-code">{{ aff.code_mission }}</code>
                                <span class="bgc-lib">{{ aff.libelle }}</span>
                                <span class="mc-status" :class="`mcs-${aff.status}`">{{ statusLabel(aff.status) }}</span>
                                <strong class="bgc-total">{{ formatMontant(aff.budget_individuel ?? 0) }} FCFA</strong>
                            </div>
                            <table v-if="(budgetLignes[aff.id] ?? []).length" class="bg-table">
                                <thead>
                                    <tr><th>Libellé</th><th>Entité</th><th class="bt-r">Montant</th></tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(l, i) in budgetLignes[aff.id]" :key="i">
                                        <td>{{ l.libelle }}</td>
                                        <td class="bt-ent">{{ l.entity_name }}</td>
                                        <td class="bt-r bt-money">{{ formatMontant(l.montant) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                            <p v-else class="bgc-nolines">Aucune ligne détaillée — enveloppe globale.</p>
                        </section>
                    </div>
                </template>

            </div>
        </div>

        <!-- ══════════════════ MODALE DÉCLARATION D'INDÉPENDANCE ══════════════════ -->
        <Teleport to="body">
            <transition name="ad-fade">
                <div v-if="showIndependenceModal" class="ind-ovl" @click.self="showIndependenceModal = false">
                    <div class="ind-box">
                        <div class="ind-head">
                            <div class="ind-shield"><i class="ti ti-shield-check"></i></div>
                            <div>
                                <h3>Déclaration d'Indépendance</h3>
                                <p>À confirmer avant le démarrage de la mission</p>
                            </div>
                            <button class="ind-x" @click="showIndependenceModal = false"><i class="ti ti-x"></i></button>
                        </div>

                        <div class="ind-mission" v-if="missionToStart">
                            <code>{{ missionToStart.code_mission }}</code>
                            <span>{{ missionToStart.libelle }}</span>
                        </div>

                        <div class="ind-checks">
                            <label v-for="(c, i) in INDEPENDENCE_ITEMS" :key="i" class="ind-check" :class="{ ok: independenceChecks[i] }">
                                <input type="checkbox" v-model="independenceChecks[i]" />
                                <span class="ind-box-check"><i class="ti ti-check"></i></span>
                                <span class="ind-txt"><strong>{{ c.t }}</strong> — {{ c.d }}</span>
                            </label>
                        </div>

                        <div v-if="showIndependenceError" class="ind-err">
                            <i class="ti ti-alert-triangle"></i>
                            Vous devez accepter toutes les déclarations pour continuer.
                        </div>

                        <div class="ind-foot">
                            <button class="m-btn m-btn-ghost" @click="showIndependenceModal = false">Annuler</button>
                            <button class="m-btn m-btn-start" :disabled="!allIndependenceChecked"
                                @click="confirmStartMissionAfterDeclaration">
                                <i class="ti ti-player-play"></i> Confirmer le démarrage
                            </button>
                        </div>
                    </div>
                </div>
            </transition>
        </Teleport>

        <!-- ══════════════════ TOAST ══════════════════ -->
        <Teleport to="body">
            <transition name="ad-toast">
                <div v-if="toast.show" class="ad-toast" :class="`adt-${toast.type}`">
                    <i :class="toast.type === 'success' ? 'ti ti-circle-check' : 'ti ti-circle-x'"></i>
                    {{ toast.message }}
                    <button @click="toast.show = false"><i class="ti ti-x"></i></button>
                </div>
            </transition>
        </Teleport>
    </VerticalLayout>
</template>

<script setup lang="ts">
import VerticalLayout from '@/layouts/VerticalLayoutAudit.vue';
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

// ─── PROPS (contrat AuditorDashboardController::index) ────────────────────
const props = defineProps({
    auditor:             { type: Object, default: () => ({}) },
    affectations:        { type: Array as () => any[], default: () => [] },
    affectationEntities: { type: Array as () => any[], default: () => [] },
    equipesParMission:   { type: Object, default: () => ({}) },
    budgetLignes:        { type: Object, default: () => ({}) },
    stats:               { type: Object, default: () => ({}) },
    prochaineMission:    { type: Object, default: null },
    missionEnCours:      { type: Object, default: null },
    calendrier:          { type: Array as () => any[], default: () => [] },
    currentYear:         { type: Number, default: new Date().getFullYear() },
    missionsDemarrables: { type: Array as () => any[], default: () => [] },
});

const auditor             = computed(() => props.auditor as any);
const stats               = computed(() => props.stats as any);
const calendrier          = computed(() => props.calendrier as any[]);
const affectations        = computed(() => props.affectations as any[]);
const budgetLignes        = computed(() => props.budgetLignes as Record<number, any[]>);
const missionEnCours      = computed(() => props.missionEnCours as any);
const prochaineMission    = computed(() => props.prochaineMission as any);
const missionsDemarrables = computed(() => props.missionsDemarrables as any[]);
const currentYear         = computed(() => props.currentYear);

// ─── ÉTAT ─────────────────────────────────────────────────────────────────
// Deep-link ?tab=missions|calendrier|budget (routes auditor.planning / .budget)
const VALID_TABS = ['overview', 'missions', 'calendrier', 'budget'] as const;
const initialTab = (() => {
    const t = new URLSearchParams(window.location.search).get('tab') ?? '';
    return (VALID_TABS as readonly string[]).includes(t) ? t : 'overview';
})() as 'overview' | 'missions' | 'calendrier' | 'budget';
const activeTab             = ref<'overview' | 'missions' | 'calendrier' | 'budget'>(initialTab);
const filterStatus          = ref('');
const searchMission         = ref('');
const selectedMonth         = ref<number | null>(null);
const showIndependenceModal = ref(false);
const missionToStart        = ref<any>(null);
const independenceChecks    = ref([false, false, false, false]);
const showIndependenceError = ref(false);
const toast                 = ref({ show: false, message: '', type: 'success' as 'success' | 'error' });

const INDEPENDENCE_ITEMS = [
    { t: 'Indépendance',     d: "je confirme n'avoir aucun conflit d'intérêt avec les entités auditées" },
    { t: 'Confidentialité',  d: 'je m\'engage à respecter la confidentialité et le secret professionnel' },
    { t: 'Professionnalisme', d: 'je m\'engage à respecter les normes et standards d\'audit' },
    { t: 'Documentation',    d: 'je m\'engage à documenter convenablement tous les travaux réalisés' },
];

// ─── COMPUTED ─────────────────────────────────────────────────────────────
const allIndependenceChecked = computed(() => independenceChecks.value.every(Boolean));

const tabs = computed(() => [
    { id: 'overview',   label: "Vue d'ensemble", icon: 'layout-dashboard', badge: null },
    { id: 'missions',   label: 'Mes Missions',   icon: 'briefcase',        badge: missionsDemarrables.value.length || null },
    { id: 'calendrier', label: 'Planning',       icon: 'calendar-stats',   badge: null },
    { id: 'budget',     label: 'Budget',         icon: 'coin',             badge: null },
]);

const kpis = computed(() => [
    { key: 'total',   icon: 'ti ti-clipboard-list', value: stats.value.mes_missions ?? 0,                      label: 'Missions' },
    { key: 'run',     icon: 'ti ti-loader-2',       value: stats.value.en_cours ?? 0,                          label: 'En cours' },
    { key: 'plan',    icon: 'ti ti-calendar-clock', value: stats.value.planifiees ?? 0,                        label: 'Planifiées' },
    { key: 'done',    icon: 'ti ti-circle-check',   value: stats.value.terminees ?? 0,                         label: 'Terminées' },
    { key: 'days',    icon: 'ti ti-clock',          value: (stats.value.jours_total ?? 0) + 'j',               label: 'Jours-audit' },
    { key: 'budget',  icon: 'ti ti-coin',           value: formatMontantCourt(stats.value.budget_total ?? 0),  label: 'Budget FCFA' },
]);

const statusFilters = [
    { v: '',          l: 'Toutes' },
    { v: 'en_cours',  l: 'En cours' },
    { v: 'planifiee', l: 'Planifiées' },
    { v: 'terminee',  l: 'Terminées' },
    { v: 'annulee',   l: 'Annulées' },
];

const filteredMissions = computed(() => {
    const q = searchMission.value.trim().toLowerCase();
    return affectations.value.filter((a: any) => {
        const mq = !q || [a.code_mission, a.libelle, a.entities_list, a.objectif]
            .some(s => String(s ?? '').toLowerCase().includes(q));
        return mq && (!filterStatus.value || a.status === filterStatus.value);
    });
});

const budgetMissions = computed(() =>
    affectations.value.filter((a: any) => (a.budget_individuel ?? 0) > 0 || (budgetLignes.value[a.id] ?? []).length)
);

const selectedMonthData = computed(() =>
    selectedMonth.value === null ? null : calendrier.value.find((m: any) => m.mois === selectedMonth.value) ?? null
);

// ─── HELPERS ──────────────────────────────────────────────────────────────
function phasesUrl(missionId: number) {
    return `/m/audit.core/auditor/missions/${missionId}/phases`;
}

function teamOf(missionId: number): any[] {
    return (props.equipesParMission as any)[missionId]?.membres ?? [];
}

function countByStatus(s: string) {
    if (!s) return affectations.value.length;
    return affectations.value.filter((a: any) => a.status === s).length;
}

function pct(n?: number) {
    const total = stats.value.mes_missions || 1;
    return Math.round(((n ?? 0) / total) * 100);
}

function initials(last?: string, first?: string) {
    return ((last?.[0] ?? '') + (first?.[0] ?? '')).toUpperCase() || '?';
}

function statusLabel(s: string): string {
    return ({ planifiee: 'Planifiée', en_cours: 'En cours', terminee: 'Terminée', annulee: 'Annulée' } as any)[s] ?? s;
}

function formatMontant(n: number): string {
    return new Intl.NumberFormat('fr-FR').format(Math.round(n || 0));
}

function formatMontantCourt(n: number): string {
    if (!n) return '0';
    if (n >= 1_000_000) return (Math.round(n / 10_000) / 100).toFixed(2).replace('.', ',') + 'M';
    if (n >= 1_000) return Math.round(n / 1_000) + 'K';
    return String(n);
}

function canStart(aff: any): boolean {
    if (!aff || aff.status !== 'planifiee') return false;
    const today = new Date(); today.setHours(0, 0, 0, 0);
    const debut = new Date(aff.date_debut || 0); debut.setHours(0, 0, 0, 0);
    return debut <= today;
}

// ─── ACTIONS ──────────────────────────────────────────────────────────────
function confirmStartMission(aff: any) {
    missionToStart.value = aff;
    independenceChecks.value = [false, false, false, false];
    showIndependenceError.value = false;
    showIndependenceModal.value = true;
}

function confirmStartMissionAfterDeclaration() {
    if (!allIndependenceChecked.value) {
        showIndependenceError.value = true;
        return;
    }
    showIndependenceModal.value = false;
    startMission(missionToStart.value);
}

function startMission(aff: any) {
    // Route réelle : auditor.missions.start
    // (PATCH /mon-espace/programmation-missions/{id}/start → status en_cours)
    router.patch(route('auditor.missions.start', aff.mission_id),
        { status: 'en_cours' },
        {
            onSuccess: () => {
                showToast('✓ Mission démarrée avec succès ! Déclaration enregistrée.', 'success');
                missionToStart.value = null;
            },
            onError: () => showToast('Erreur lors du démarrage de la mission.', 'error'),
        }
    );
}

function handleLogout() {
    if (confirm('Êtes-vous certain de vouloir vous déconnecter ?')) {
        router.post(route('logout'));
    }
}

let toastTimer: ReturnType<typeof setTimeout>;
function showToast(message: string, type: 'success' | 'error') {
    toast.value = { show: true, message, type };
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => { toast.value.show = false; }, 4000);
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,600;9..40,800&family=JetBrains+Mono:wght@400;700&display=swap');

* { box-sizing: border-box; }

.ad-shell {
    font-family: 'DM Sans', sans-serif;
    min-height: calc(100vh - 68px);
    background: #060c1a;
    color: #e2e8f0;
    overflow-x: hidden;
}

/* ══ HERO ══ */
.ad-hero {
    position: relative;
    padding: 26px 26px 20px;
    background: linear-gradient(135deg, #0a1628 0%, #0f1e3a 45%, #0d1527 100%);
    border-bottom: 1px solid rgba(255,255,255,.06);
    overflow: hidden;
}
.hero-mesh {
    position: absolute; inset: 0; pointer-events: none;
    background:
        radial-gradient(ellipse 55% 80% at 85% 40%, rgba(37,99,235,.13) 0%, transparent 70%),
        radial-gradient(ellipse 40% 60% at 15% 90%, rgba(124,58,237,.09) 0%, transparent 60%);
}
.hero-inner {
    position: relative;
    display: flex; align-items: center; justify-content: space-between;
    gap: 22px; flex-wrap: wrap;
}
.hero-id { display: flex; align-items: center; gap: 15px; min-width: 0; }
.hero-avatar {
    position: relative; width: 64px; height: 64px; border-radius: 18px;
    overflow: hidden; flex-shrink: 0;
    border: 2px solid rgba(37,99,235,.5);
    box-shadow: 0 0 0 4px rgba(37,99,235,.1), 0 8px 24px rgba(0,0,0,.4);
    background: #1e3a5f; display: flex; align-items: center; justify-content: center;
}
.hero-avatar img { width: 100%; height: 100%; object-fit: cover; }
.ha-initials { font-size: 1.4rem; font-weight: 800; color: #60a5fa; }
.ha-dot {
    position: absolute; bottom: 4px; right: 4px;
    width: 10px; height: 10px; border-radius: 50%; border: 2px solid #0a1628;
}
.ha-on  { background: #10b981; }
.ha-off { background: #ef4444; }

.hero-who { display: flex; flex-direction: column; gap: 4px; min-width: 0; }
.hw-kicker { margin: 0; font-size: .64rem; text-transform: uppercase; letter-spacing: .12em; color: #60a5fa; font-weight: 700; }
.hw-name { margin: 0; font-size: 1.35rem; font-weight: 800; color: #f1f5f9; letter-spacing: -.02em; }
.hw-meta { display: flex; gap: 6px; flex-wrap: wrap; }
.hw-pill {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: .66rem; color: #94a3b8;
    background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.08);
    padding: 2px 9px; border-radius: 20px;
}
.hw-code { font-family: 'JetBrains Mono', monospace; color: #7dd3fc; }

.hero-logout {
    width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0;
    border: 1px solid rgba(248,113,113,.25); background: rgba(248,113,113,.08);
    color: #f87171; cursor: pointer;
    display: inline-flex; align-items: center; justify-content: center;
    transition: all .15s;
}
.hero-logout:hover { background: #dc2626; color: #fff; }

/* KPIs */
.hero-kpis { display: flex; gap: 8px; flex-wrap: wrap; }
.kpi {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 14px; border-radius: 12px; min-width: 96px;
    background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.07);
    backdrop-filter: blur(10px);
}
.kpi > i { font-size: 1.05rem; opacity: .85; }
.kpi-txt { display: flex; flex-direction: column; }
.kpi-v { font-size: 1.05rem; font-weight: 800; color: #f1f5f9; line-height: 1.1; }
.kpi-l { font-size: .58rem; text-transform: uppercase; letter-spacing: .08em; color: #64748b; }
.kpi-total  > i { color: #60a5fa; }
.kpi-run    > i { color: #fbbf24; }
.kpi-plan   > i { color: #a78bfa; }
.kpi-done   > i { color: #34d399; }
.kpi-days   > i { color: #f472b6; }
.kpi-budget > i { color: #4ade80; }
.kpi-ring { width: 34px; height: 34px; transform: rotate(-90deg); flex-shrink: 0; }
.kpi-taux .kpi-v { color: #34d399; }

/* Alerte démarrables */
.hero-alert {
    position: relative;
    margin-top: 14px;
    display: flex; align-items: center; gap: 10px;
    padding: 9px 14px; border-radius: 10px; cursor: pointer;
    background: rgba(251,191,36,.1); border: 1px solid rgba(251,191,36,.3);
    color: #fbbf24; font-size: .78rem;
    transition: background .15s;
}
.hero-alert:hover { background: rgba(251,191,36,.16); }
.hero-alert > i { font-size: 1rem; }
.ha-cta { margin-left: auto; font-weight: 800; display: inline-flex; align-items: center; gap: 4px; }

/* ══ FOCUS (mission en cours / prochaine) ══ */
.ad-focus {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 14px; padding: 16px 20px 0;
}
.focus-card {
    position: relative; border-radius: 14px; padding: 15px 17px;
    display: flex; flex-direction: column; gap: 8px;
    border: 1px solid rgba(255,255,255,.07); background: #0d1627;
    overflow: hidden;
}
.focus-current { border-color: rgba(251,191,36,.25); background: linear-gradient(135deg, #14202f, #0d1627 70%); }
.focus-next    { border-color: rgba(167,139,250,.22); }
.fc-head { display: flex; align-items: center; gap: 8px; }
.fc-tag {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: .62rem; font-weight: 800; text-transform: uppercase; letter-spacing: .06em;
    padding: 3px 9px; border-radius: 14px;
}
.fc-tag-run  { background: rgba(251,191,36,.14); color: #fbbf24; }
.fc-tag-next { background: rgba(167,139,250,.14); color: #a78bfa; }
.fc-code {
    font-family: 'JetBrains Mono', monospace; font-size: .66rem; font-weight: 700;
    color: #60a5fa; background: rgba(37,99,235,.12); padding: 2px 7px; border-radius: 4px;
}
.fc-title { margin: 0; font-size: .95rem; font-weight: 700; color: #f1f5f9; line-height: 1.3; }
.fc-meta { display: flex; flex-wrap: wrap; gap: 10px; }
.fc-meta span { display: inline-flex; align-items: center; gap: 4px; font-size: .68rem; color: #64748b; }
.fc-role { font-weight: 800; padding: 1px 7px; border-radius: 8px; }
.fc-prog { display: flex; align-items: center; gap: 8px; }
.fc-bar { flex: 1; height: 5px; border-radius: 3px; background: rgba(255,255,255,.07); overflow: hidden; }
.fc-fill { height: 100%; border-radius: 3px; background: linear-gradient(90deg, #d97706, #fbbf24); transition: width .4s; }
.fc-pct { font-size: .68rem; font-weight: 800; color: #fbbf24; }
.fc-btn {
    align-self: flex-start;
    display: inline-flex; align-items: center; gap: 6px;
    font-size: .74rem; font-weight: 700; padding: 7px 14px; border-radius: 9px;
    background: #2563eb; color: #fff; text-decoration: none; border: none; cursor: pointer;
    transition: filter .12s;
}
.fc-btn:hover { filter: brightness(1.12); }
.fc-btn-start { background: #059669; }
.fc-wait { display: inline-flex; align-items: center; gap: 5px; font-size: .7rem; color: #64748b; }

/* ══ ONGLETS ══ */
.ad-tabs {
    display: flex; gap: 4px; flex-wrap: wrap;
    padding: 16px 20px 0;
}
.ad-tab {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 16px; border-radius: 10px 10px 0 0;
    background: transparent; border: 1px solid transparent; border-bottom: none;
    color: #64748b; font-size: .78rem; font-weight: 700;
    cursor: pointer; transition: all .14s; font-family: inherit;
}
.ad-tab:hover { color: #93c5fd; }
.ad-tab.on {
    color: #f1f5f9;
    background: #0d1627;
    border-color: rgba(255,255,255,.07);
    box-shadow: inset 0 2px 0 #2563eb;
}
.ad-tab-badge {
    min-width: 17px; height: 17px; padding: 0 5px; border-radius: 9px;
    background: #dc2626; color: #fff; font-size: .58rem; font-weight: 800;
    display: inline-flex; align-items: center; justify-content: center;
}

.ad-body { padding: 0 20px 30px; }

/* ══ CARTES GÉNÉRIQUES ══ */
.ad-card {
    background: #0d1627; border: 1px solid rgba(255,255,255,.07);
    border-radius: 0 14px 14px 14px; padding: 16px 18px;
}
.adc-label {
    display: flex; align-items: center; gap: 7px;
    font-size: .68rem; font-weight: 800; text-transform: uppercase; letter-spacing: .07em;
    color: #64748b; margin-bottom: 14px;
}
.adc-label i { color: #60a5fa; }

/* ══ OVERVIEW ══ */
.ov-grid { display: grid; grid-template-columns: 1fr 320px; gap: 14px; align-items: start; }
@media (max-width: 1100px) { .ov-grid { grid-template-columns: 1fr; } }

.year-strip {
    display: grid; grid-template-columns: repeat(12, 1fr); gap: 6px;
}
@media (max-width: 900px) { .year-strip { grid-template-columns: repeat(6, 1fr); } }
.ys-month {
    position: relative;
    display: flex; flex-direction: column; align-items: center; gap: 3px;
    padding: 9px 4px 7px; border-radius: 10px;
    border: 1px solid rgba(255,255,255,.06); background: rgba(255,255,255,.02);
    cursor: pointer; transition: all .13s; font-family: inherit;
}
.ys-month:hover { border-color: rgba(37,99,235,.4); }
.ys-month.on { border-color: #2563eb; box-shadow: 0 0 0 2px rgba(37,99,235,.25); }
.ys-lbl { font-size: .64rem; font-weight: 800; color: #cbd5e1; }
.ys-jours { font-size: .58rem; color: #64748b; font-family: 'JetBrains Mono', monospace; }
.ys-n {
    position: absolute; top: -5px; right: -4px;
    min-width: 15px; height: 15px; padding: 0 4px; border-radius: 8px;
    background: #2563eb; color: #fff; font-size: .54rem; font-weight: 800;
    display: flex; align-items: center; justify-content: center;
}
.ys-en_cours  { background: rgba(251,191,36,.1);  border-color: rgba(251,191,36,.35); }
.ys-planifiee { background: rgba(167,139,250,.1); border-color: rgba(167,139,250,.3); }
.ys-terminee  { background: rgba(52,211,153,.08); border-color: rgba(52,211,153,.28); }
.ys-annulee   { background: rgba(248,113,113,.08); border-color: rgba(248,113,113,.25); }

.year-legend { display: flex; gap: 14px; flex-wrap: wrap; margin-top: 12px; }
.year-legend span { display: inline-flex; align-items: center; gap: 5px; font-size: .62rem; color: #64748b; }
.lg { width: 8px; height: 8px; border-radius: 3px; display: inline-block; }
.lg-en_cours  { background: #fbbf24; }
.lg-planifiee { background: #a78bfa; }
.lg-terminee  { background: #34d399; }
.lg-annulee   { background: #f87171; }
.lg-libre     { background: rgba(148,163,184,.3); }

.ym-detail {
    margin-top: 14px; border-top: 1px solid rgba(255,255,255,.06); padding-top: 12px;
    display: flex; flex-direction: column; gap: 6px;
}
.ymd-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
.ymd-head strong { font-size: .82rem; color: #f1f5f9; }
.ymd-head span { font-size: .66rem; color: #64748b; }
.ymd-item {
    display: flex; align-items: center; gap: 9px; flex-wrap: wrap;
    padding: 7px 10px; border-radius: 9px; background: rgba(255,255,255,.025);
    font-size: .7rem;
}
.ymd-item code { font-family: 'JetBrains Mono', monospace; font-size: .62rem; color: #60a5fa; }
.ymd-lib { flex: 1; min-width: 140px; color: #cbd5e1; font-weight: 600; }
.ymd-ent { color: #64748b; display: inline-flex; align-items: center; gap: 3px; }
.ymd-dates { font-family: 'JetBrains Mono', monospace; font-size: .6rem; color: #475569; }
.ymd-j { font-size: .6rem; font-weight: 700; color: #a78bfa; background: rgba(124,58,237,.12); padding: 1px 6px; border-radius: 8px; }
.ymd-empty { margin: 0; font-size: .7rem; color: #475569; }

.rep-rows { display: flex; flex-direction: column; gap: 10px; }
.rep-row { display: flex; align-items: center; gap: 10px; }
.rep-lbl { width: 92px; display: inline-flex; align-items: center; gap: 6px; font-size: .68rem; color: #94a3b8; flex-shrink: 0; }
.rep-bar { flex: 1; height: 7px; border-radius: 4px; background: rgba(255,255,255,.06); overflow: hidden; }
.rep-fill { height: 100%; border-radius: 4px; transition: width .5s; }
.rf-en_cours  { background: linear-gradient(90deg, #d97706, #fbbf24); }
.rf-planifiee { background: linear-gradient(90deg, #7c3aed, #a78bfa); }
.rf-terminee  { background: linear-gradient(90deg, #059669, #34d399); }
.rf-annulee   { background: linear-gradient(90deg, #dc2626, #f87171); }
.rep-row strong { width: 26px; text-align: right; font-size: .78rem; color: #e2e8f0; }
.rep-tot {
    margin-top: 16px; border-top: 1px solid rgba(255,255,255,.06); padding-top: 12px;
    display: flex; flex-direction: column; gap: 8px;
}
.rep-tot > div { display: flex; align-items: center; justify-content: space-between; }
.rep-tot span { font-size: .68rem; color: #64748b; }
.rep-tot strong { font-size: .78rem; color: #e2e8f0; }
.rep-money { color: #4ade80 !important; }
.rep-risk { color: #f87171 !important; }

/* ══ MISSIONS ══ */
.ms-toolbar {
    display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
    background: #0d1627; border: 1px solid rgba(255,255,255,.07);
    border-radius: 0 14px 0 0; padding: 12px 16px; border-bottom: none;
}
.ms-search {
    display: flex; align-items: center; gap: 6px;
    background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.07);
    border-radius: 9px; padding: 0 10px; min-width: 230px;
}
.ms-search:focus-within { border-color: rgba(37,99,235,.5); }
.ms-search i { color: #475569; font-size: .78rem; }
.ms-search input {
    background: none; border: none; outline: none;
    color: #e2e8f0; font-size: .76rem; padding: 7px 0; flex: 1; font-family: inherit;
}
.ms-search input::placeholder { color: #475569; }
.ms-clear { background: none; border: none; color: #475569; cursor: pointer; padding: 2px; }
.ms-clear:hover { color: #e2e8f0; }
.ms-filters { display: flex; gap: 4px; flex-wrap: wrap; margin-left: auto; }
.ms-f {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 11px; border-radius: 16px;
    border: 1px solid rgba(255,255,255,.07); background: rgba(255,255,255,.03);
    color: #64748b; font-size: .66rem; font-weight: 700; cursor: pointer;
    transition: all .13s; font-family: inherit;
}
.ms-f:hover { color: #93c5fd; border-color: rgba(37,99,235,.4); }
.ms-f.on { background: rgba(37,99,235,.15); border-color: rgba(37,99,235,.4); color: #93c5fd; }
.ms-f.msf-en_cours.on  { background: rgba(251,191,36,.1);  border-color: rgba(251,191,36,.3);  color: #fbbf24; }
.ms-f.msf-planifiee.on { background: rgba(167,139,250,.1); border-color: rgba(167,139,250,.3); color: #a78bfa; }
.ms-f.msf-terminee.on  { background: rgba(52,211,153,.1);  border-color: rgba(52,211,153,.3);  color: #34d399; }
.ms-f.msf-annulee.on   { background: rgba(248,113,113,.1); border-color: rgba(248,113,113,.3); color: #f87171; }
.ms-f-n { font-size: .56rem; background: rgba(255,255,255,.08); padding: 1px 5px; border-radius: 8px; }

.ms-list {
    display: flex; flex-direction: column; gap: 12px;
    background: #0d1627; border: 1px solid rgba(255,255,255,.07);
    border-radius: 0 0 14px 14px; padding: 14px 16px;
}

.m-card {
    display: flex; border-radius: 12px; overflow: hidden;
    background: #101b2e; border: 1px solid rgba(255,255,255,.06);
    transition: border-color .15s, transform .15s;
}
.m-card:hover { border-color: rgba(37,99,235,.3); transform: translateY(-1px); }
.m-stripe { width: 4px; flex-shrink: 0; }
.stripe-planifiee { background: linear-gradient(180deg, #a78bfa, #7c3aed); }
.stripe-en_cours  { background: linear-gradient(180deg, #fbbf24, #d97706); }
.stripe-terminee  { background: linear-gradient(180deg, #34d399, #059669); }
.stripe-annulee   { background: linear-gradient(180deg, #f87171, #dc2626); }
.m-inner { flex: 1; padding: 13px 15px; display: flex; flex-direction: column; gap: 9px; min-width: 0; }

.m-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
.m-head-l { display: flex; align-items: center; gap: 7px; flex-wrap: wrap; }
.m-code {
    font-family: 'JetBrains Mono', monospace; font-size: .66rem; font-weight: 700;
    color: #60a5fa; background: rgba(37,99,235,.12); padding: 2px 7px; border-radius: 4px;
}
.mc-status {
    font-size: .56rem; font-weight: 800; padding: 2px 8px; border-radius: 16px;
    text-transform: uppercase; letter-spacing: .05em;
}
.mcs-planifiee { background: rgba(167,139,250,.15); color: #a78bfa; }
.mcs-en_cours  { background: rgba(251,191,36,.15);  color: #fbbf24; }
.mcs-terminee  { background: rgba(52,211,153,.15);  color: #34d399; }
.mcs-annulee   { background: rgba(248,113,113,.15); color: #f87171; }
.m-role {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: .6rem; font-weight: 700; padding: 2px 8px; border-radius: 8px;
}
.rb-DM { background: rgba(251,191,36,.15);  color: #fbbf24; }
.rb-CM { background: rgba(96,165,250,.15);  color: #60a5fa; }
.rb-AS { background: rgba(52,211,153,.15);  color: #34d399; }
.rb-AJ { background: rgba(167,139,250,.15); color: #a78bfa; }
.m-start-flag {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: .6rem; font-weight: 800; padding: 2px 9px; border-radius: 10px;
    background: rgba(52,211,153,.15); color: #34d399;
    animation: pulse-soft 1.8s ease-in-out infinite;
}
@keyframes pulse-soft { 50% { opacity: .55; } }

.m-actions { display: flex; gap: 6px; flex-shrink: 0; }
.m-btn {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: .7rem; font-weight: 700; padding: 6px 13px; border-radius: 8px;
    border: 1px solid transparent; cursor: pointer; text-decoration: none;
    transition: all .13s; font-family: inherit;
}
.m-btn:disabled { opacity: .5; cursor: not-allowed; }
.m-btn-start { background: #059669; color: #fff; }
.m-btn-start:hover:not(:disabled) { background: #10b981; }
.m-btn-go { background: rgba(37,99,235,.15); border-color: rgba(37,99,235,.3); color: #60a5fa; }
.m-btn-go:hover { background: #2563eb; color: #fff; }
.m-btn-ghost { background: rgba(255,255,255,.05); border-color: rgba(255,255,255,.1); color: #94a3b8; }
.m-btn-ghost:hover { color: #e2e8f0; }

.m-title { margin: 0; font-size: .88rem; font-weight: 700; color: #f1f5f9; line-height: 1.3; }
.m-obj {
    margin: 0; display: flex; align-items: center; gap: 5px;
    font-size: .68rem; color: #64748b;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}

.m-grid {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 8px;
}
.mg-cell {
    display: flex; flex-direction: column; gap: 3px; min-width: 0;
    background: rgba(255,255,255,.025); border: 1px solid rgba(255,255,255,.05);
    border-radius: 9px; padding: 8px 10px;
}
.mg-l { display: inline-flex; align-items: center; gap: 4px; font-size: .56rem; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; color: #475569; }
.mg-v { font-size: .72rem; font-weight: 600; color: #cbd5e1; }
.mg-sub { font-size: .62rem; color: #475569; display: inline-flex; align-items: center; gap: 3px; }
.mg-trunc { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.mg-money { color: #4ade80; }
.mg-risk { color: #f87171; font-weight: 800; }
.mg-ok { color: #34d399; }
.mg-team { display: flex; align-items: center; }
.tm-av {
    width: 22px; height: 22px; border-radius: 50%; margin-left: -5px;
    display: flex; align-items: center; justify-content: center;
    font-size: .5rem; font-weight: 800; border: 2px solid #101b2e;
}
.tm-av:first-child { margin-left: 0; }
.tm-av.me { box-shadow: 0 0 0 2px #10b981; }
.av-DM { background: rgba(251,191,36,.2);  color: #fbbf24; }
.av-CM { background: rgba(96,165,250,.2);  color: #60a5fa; }
.av-AS { background: rgba(52,211,153,.2);  color: #34d399; }
.av-AJ { background: rgba(167,139,250,.2); color: #a78bfa; }
.tm-more { margin-left: 3px; font-size: .58rem; color: #64748b; }

.m-prog { display: flex; align-items: center; gap: 8px; }
.mp-bar { flex: 1; height: 4px; border-radius: 3px; background: rgba(255,255,255,.06); overflow: hidden; }
.mp-fill { height: 100%; border-radius: 3px; transition: width .4s; }
.pf-en_cours  { background: linear-gradient(90deg, #d97706, #fbbf24); }
.pf-planifiee { background: linear-gradient(90deg, #7c3aed, #a78bfa); }
.pf-terminee  { background: linear-gradient(90deg, #059669, #34d399); }
.pf-annulee   { background: #dc2626; }
.mp-pct { font-size: .64rem; font-weight: 800; color: #94a3b8; }

/* ══ PLANNING ══ */
.pl-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 10px; }
.pl-month {
    border: 1px solid rgba(255,255,255,.06); border-radius: 12px;
    padding: 11px 13px; background: rgba(255,255,255,.02);
    display: flex; flex-direction: column; gap: 8px;
}
.pl-en_cours  { border-color: rgba(251,191,36,.3); }
.pl-planifiee { border-color: rgba(167,139,250,.28); }
.pl-terminee  { border-color: rgba(52,211,153,.25); }
.plm-head { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
.plm-head strong { font-size: .78rem; color: #f1f5f9; }
.plm-status { font-size: .54rem; font-weight: 800; padding: 2px 7px; border-radius: 12px; text-transform: uppercase; }
.plm-stats { display: flex; gap: 12px; }
.plm-stats span { display: inline-flex; align-items: center; gap: 4px; font-size: .64rem; color: #64748b; }
.plm-bar { height: 4px; border-radius: 3px; background: rgba(255,255,255,.06); overflow: hidden; }
.plm-fill { height: 100%; background: linear-gradient(90deg, #2563eb, #60a5fa); border-radius: 3px; }
.plm-items { display: flex; flex-direction: column; gap: 4px; }
.plm-it {
    display: flex; align-items: center; gap: 6px;
    font-size: .64rem; color: #94a3b8;
    background: rgba(255,255,255,.025); border-radius: 7px; padding: 4px 7px;
}
.plm-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
.plm-it code { font-family: 'JetBrains Mono', monospace; font-size: .58rem; color: #60a5fa; }
.plm-it-ent { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.plm-it-j { font-size: .56rem; color: #a78bfa; }
.plm-more { font-size: .6rem; color: #475569; padding-left: 4px; }
.plm-free { font-size: .62rem; color: #334155; font-style: italic; }

/* ══ BUDGET ══ */
.bg-top { margin-bottom: 12px; }
.bg-total { border-radius: 0 14px 14px 14px; }
.bgt-amount { font-size: 1.6rem; font-weight: 800; color: #4ade80; }
.bgt-amount em { font-style: normal; font-size: .8rem; color: #64748b; }
.bgt-sub { font-size: .7rem; color: #64748b; margin-top: 2px; }
.bg-list { display: flex; flex-direction: column; gap: 12px; }
.bg-card { border-radius: 14px; }
.bgc-head { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 10px; }
.bgc-lib { flex: 1; min-width: 150px; font-size: .8rem; font-weight: 700; color: #e2e8f0; }
.bgc-total { font-size: .85rem; color: #4ade80; }
.bg-table { width: 100%; border-collapse: collapse; font-size: .72rem; }
.bg-table th {
    text-align: left; padding: 6px 9px;
    font-size: .56rem; font-weight: 800; text-transform: uppercase; letter-spacing: .06em;
    color: #475569; border-bottom: 1px solid rgba(255,255,255,.06);
}
.bg-table td { padding: 7px 9px; color: #cbd5e1; border-bottom: 1px solid rgba(255,255,255,.04); }
.bt-r { text-align: right; }
.bt-ent { color: #64748b; }
.bt-money { font-family: 'JetBrains Mono', monospace; color: #4ade80; }
.bgc-nolines { margin: 0; font-size: .68rem; color: #475569; font-style: italic; }

/* ══ EMPTY ══ */
.ad-empty {
    display: flex; flex-direction: column; align-items: center; gap: 8px;
    padding: 60px 20px; color: #334155;
    background: #0d1627; border: 1px solid rgba(255,255,255,.07);
    border-radius: 0 0 14px 14px;
}
.ad-empty i { font-size: 2rem; }
.ad-empty p { margin: 0; font-size: .9rem; font-weight: 600; color: #475569; }
.ad-empty span { font-size: .74rem; }

/* ══ MODALE INDÉPENDANCE ══ */
.ind-ovl {
    position: fixed; inset: 0; z-index: 1000;
    background: rgba(2,6,17,.75); backdrop-filter: blur(6px);
    display: flex; align-items: center; justify-content: center; padding: 20px;
}
.ind-box {
    width: min(560px, 96vw); max-height: 92vh; overflow-y: auto;
    background: #0d1627; border: 1px solid rgba(255,255,255,.1);
    border-radius: 16px; padding: 20px 22px;
    box-shadow: 0 24px 80px rgba(0,0,0,.6);
    font-family: 'DM Sans', sans-serif;
    display: flex; flex-direction: column; gap: 14px;
}
.ind-head { display: flex; align-items: flex-start; gap: 12px; }
.ind-shield {
    width: 44px; height: 44px; border-radius: 12px; flex-shrink: 0;
    background: rgba(52,211,153,.12); color: #34d399;
    display: flex; align-items: center; justify-content: center; font-size: 1.3rem;
}
.ind-head h3 { margin: 0; font-size: 1rem; font-weight: 800; color: #f1f5f9; }
.ind-head p { margin: 2px 0 0; font-size: .72rem; color: #64748b; }
.ind-x {
    margin-left: auto; width: 30px; height: 30px; border-radius: 8px;
    border: 1px solid rgba(255,255,255,.08); background: rgba(255,255,255,.04);
    color: #64748b; cursor: pointer;
}
.ind-x:hover { color: #e2e8f0; }
.ind-mission {
    display: flex; align-items: center; gap: 9px;
    background: rgba(37,99,235,.08); border: 1px solid rgba(37,99,235,.2);
    border-radius: 10px; padding: 9px 12px;
}
.ind-mission code { font-family: 'JetBrains Mono', monospace; font-size: .66rem; color: #60a5fa; }
.ind-mission span { font-size: .76rem; font-weight: 600; color: #e2e8f0; }
.ind-checks { display: flex; flex-direction: column; gap: 8px; }
.ind-check {
    display: flex; align-items: flex-start; gap: 10px;
    background: rgba(251,191,36,.06); border: 1px solid rgba(251,191,36,.18);
    border-radius: 10px; padding: 10px 12px; cursor: pointer;
    transition: all .14s;
}
.ind-check:hover { border-color: rgba(251,191,36,.4); }
.ind-check.ok { background: rgba(52,211,153,.07); border-color: rgba(52,211,153,.35); }
.ind-check input { display: none; }
.ind-box-check {
    width: 19px; height: 19px; border-radius: 6px; flex-shrink: 0; margin-top: 1px;
    border: 1.5px solid rgba(255,255,255,.2);
    display: flex; align-items: center; justify-content: center;
    color: transparent; font-size: .68rem; transition: all .14s;
}
.ind-check.ok .ind-box-check { background: #10b981; border-color: #10b981; color: #fff; }
.ind-txt { font-size: .72rem; color: #94a3b8; line-height: 1.4; }
.ind-txt strong { color: #e2e8f0; }
.ind-err {
    display: flex; align-items: center; gap: 8px;
    background: rgba(248,113,113,.1); border: 1px solid rgba(248,113,113,.3);
    border-radius: 10px; padding: 9px 12px;
    font-size: .72rem; font-weight: 600; color: #f87171;
}
.ind-foot { display: flex; justify-content: flex-end; gap: 8px; }

.ad-fade-enter-active, .ad-fade-leave-active { transition: opacity .18s ease; }
.ad-fade-enter-from, .ad-fade-leave-to { opacity: 0; }

/* ══ TOAST ══ */
.ad-toast {
    position: fixed; bottom: 22px; right: 22px; z-index: 2000;
    display: flex; align-items: center; gap: 9px;
    padding: 11px 16px; border-radius: 12px;
    font-family: 'DM Sans', sans-serif; font-size: .76rem; font-weight: 700;
    box-shadow: 0 12px 40px rgba(0,0,0,.5);
}
.adt-success { background: #064e3b; border: 1px solid rgba(52,211,153,.4); color: #6ee7b7; }
.adt-error   { background: #450a0a; border: 1px solid rgba(248,113,113,.4); color: #fca5a5; }
.ad-toast button { background: none; border: none; color: inherit; opacity: .6; cursor: pointer; padding: 2px; }
.ad-toast button:hover { opacity: 1; }
.ad-toast-enter-active, .ad-toast-leave-active { transition: all .2s ease; }
.ad-toast-enter-from, .ad-toast-leave-to { opacity: 0; transform: translateY(10px); }
</style>
