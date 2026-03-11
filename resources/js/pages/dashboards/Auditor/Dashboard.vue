<template>
    <VerticalLayout>
        <PageTitle title="Mon Espace Auditeur" :subtitle="`${auditor?.audit_code} — ${auditor?.last_name} ${auditor?.first_name}`" />

        <!-- ALERTE MISSIONS DÉMARRABLES -->
        <b-row v-if="missionsDemarrables.length > 0" class="mb-3">
            <b-col cols="12">
                <div class="alert alert-warning alert-dismissible border-0 shadow-sm d-flex align-items-center gap-3 mb-0">
                    <i class="ti ti-player-play fs-24 text-warning flex-shrink-0"></i>
                    <div class="flex-grow-1">
                        <strong>{{ missionsDemarrables.length }} mission(s) prête(s) à démarrer aujourd'hui</strong>
                    </div>
                    <button class="btn btn-warning btn-sm" @click="activeTab = 'missions'">
                        Voir <i class="ti ti-arrow-right ms-1"></i>
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </b-col>
        </b-row>

        <!-- LIGNE 1 : PROFIL + KPIs + SYNTHÈSE -->
        <b-row class="g-3 mb-3">
            <!-- Profil -->
            <b-col xxl="3" xl="4">
                <b-card no-body class="h-100">
                    <div class="d-flex card-header justify-content-between align-items-center">
                        <h4 class="header-title">Mon Profil Auditeur</h4>
                        <b-dropdown :variant="null" no-caret toggle-class="p-0 m-0 card-drop bg-light-subtle rounded">
                            <template #button-content><i class="ti ti-dots-vertical"></i></template>
                            <b-dropdown-item @click="activeTab = 'missions'">Mes Missions</b-dropdown-item>
                            <b-dropdown-item @click="activeTab = 'budget'">Mon Budget</b-dropdown-item>
                            <b-dropdown-item @click="activeTab = 'calendrier'">Planning</b-dropdown-item>
                        </b-dropdown>
                    </div>
                    <b-card-body class="pt-2">
                        <div class="text-center mb-3">
                            <div class="position-relative d-inline-block">
                                <img v-if="auditor?.avatar" :src="`/storage/${auditor.avatar}`"
                                    class="rounded-circle border border-3 border-primary" width="72" height="72" alt="avatar" />
                                <div v-else class="rounded-circle bg-primary d-flex align-items-center justify-content-center mx-auto" style="width:72px;height:72px;">
                                    <span class="text-white fw-bold fs-22">{{ auditor?.initiales }}</span>
                                </div>
                                <span class="position-absolute bottom-0 end-0 badge rounded-pill"
                                    :class="auditor?.status === 'active' ? 'bg-success' : 'bg-danger'">
                                    <i class="ti ti-circle-filled" style="font-size:8px;"></i>
                                </span>
                            </div>
                            <h5 class="mt-2 mb-0 fw-semibold">{{ auditor?.nom_complet }}</h5>
                            <span class="badge bg-primary-subtle text-primary mt-1">{{ auditor?.audit_code }}</span>
                            <div v-if="auditor?.entity" class="text-muted fs-12 mt-1">{{ auditor.entity }}</div>
                            <div v-if="auditor?.audit_experience" class="text-muted fs-12">{{ auditor.audit_experience }} an(s) d'expérience</div>
                        </div>
                        <div class="row g-2 text-center border-top pt-3">
                            <div class="col-4">
                                <h4 class="mb-0 fw-bold text-primary">{{ stats.mes_missions }}</h4>
                                <p class="text-muted fs-12 mb-0">Missions</p>
                            </div>
                            <div class="col-4 border-start border-end">
                                <h4 class="mb-0 fw-bold text-warning">{{ stats.en_cours }}</h4>
                                <p class="text-muted fs-12 mb-0">En cours</p>
                            </div>
                            <div class="col-4">
                                <h4 class="mb-0 fw-bold text-success">{{ stats.terminees }}</h4>
                                <p class="text-muted fs-12 mb-0">Terminées</p>
                            </div>
                        </div>
                        <b-row class="g-2 mt-2">
                            <b-col><button class="btn btn-primary w-100 btn-sm" @click="activeTab = 'missions'"><i class="ti ti-briefcase me-1"></i>Missions</button></b-col>
                            <b-col><button class="btn btn-info w-100 btn-sm" @click="activeTab = 'calendrier'"><i class="ti ti-calendar me-1"></i>Planning</button></b-col>
                        </b-row>
                    </b-card-body>
                </b-card>
            </b-col>

            <!-- KPI Cards -->
            <b-col xxl="6" xl="8">
                <b-row class="h-100 g-3">
                    <b-col md="4" v-for="(kpi, idx) in kpiCards" :key="idx" class="mb-3 mb-xxl-0">
                        <b-card no-body class="h-100">
                            <b-card-body>
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="avatar-sm rounded" :class="`bg-${kpi.color}-subtle`"
                                        style="display:flex;align-items:center;justify-content:center;">
                                        <i :class="`ti ti-${kpi.icon} fs-22 text-${kpi.color}`"></i>
                                    </div>
                                    <b-badge :variant="null" :class="`bg-${kpi.trendColor}-subtle text-${kpi.trendColor}`">
                                        {{ kpi.trendValue }}
                                    </b-badge>
                                </div>
                                <h3 class="fw-bold mb-1">{{ kpi.value }}</h3>
                                <p class="text-muted mb-0 fs-13">{{ kpi.label }}</p>
                                <div class="progress mt-2" style="height:4px;">
                                    <div class="progress-bar" :class="`bg-${kpi.color}`" :style="`width:${kpi.progress}%`"></div>
                                </div>
                            </b-card-body>
                        </b-card>
                    </b-col>
                </b-row>
            </b-col>

            <!-- Synthèse charge -->
            <b-col xxl="3" xl="12">
                <b-card no-body class="h-100" style="background:linear-gradient(135deg,#0F172A 60%,#1E3A8A 100%);">
                    <b-card-body class="text-white">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <p class="mb-1 text-white-50 fs-12 text-uppercase fw-semibold">Charge {{ currentYear }}</p>
                                <h2 class="mb-0 fw-bold">{{ stats.taux_realisation }}<span class="fs-16 fw-normal text-white-50">%</span></h2>
                                <small class="text-white-50">Taux de réalisation</small>
                            </div>
                            <div class="avatar rounded p-2" style="background:rgba(255,255,255,.1);">
                                <i class="ti ti-chart-bar fs-22 text-info"></i>
                            </div>
                        </div>
                        <div class="d-flex flex-column gap-2">
                            <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background:rgba(255,255,255,.07);">
                                <span class="fs-12 text-white-50"><i class="ti ti-clock me-1 text-warning"></i>Planifiées</span>
                                <span class="badge bg-warning-subtle text-warning">{{ stats.planifiees }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background:rgba(255,255,255,.07);">
                                <span class="fs-12 text-white-50"><i class="ti ti-player-play me-1 text-primary"></i>En cours</span>
                                <span class="badge bg-primary-subtle text-primary">{{ stats.en_cours }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background:rgba(255,255,255,.07);">
                                <span class="fs-12 text-white-50"><i class="ti ti-check me-1 text-success"></i>Terminées</span>
                                <span class="badge bg-success-subtle text-success">{{ stats.terminees }}</span>
                            </div>
                            <div v-if="stats.nb_risques_total > 0" class="d-flex justify-content-between align-items-center p-2 rounded" style="background:rgba(220,38,38,.15);">
                                <span class="fs-12 text-white-50"><i class="ti ti-alert-triangle me-1 text-danger"></i>Risques identifiés</span>
                                <span class="badge bg-danger-subtle text-danger">{{ stats.nb_risques_total }}</span>
                            </div>
                        </div>
                        <div class="border-top border-white border-opacity-10 mt-3 pt-3 d-flex justify-content-between">
                            <div>
                                <p class="mb-0 text-white-50 fs-11">Budget total</p>
                                <strong class="text-success fs-13">{{ formatMontantCourt(stats.budget_total) }}</strong>
                            </div>
                            <div class="text-end">
                                <p class="mb-0 text-white-50 fs-11">Jours audit</p>
                                <strong class="text-info fs-13">{{ stats.jours_total }}j</strong>
                            </div>
                        </div>
                    </b-card-body>
                </b-card>
            </b-col>
        </b-row>

        <!-- ONGLETS PRINCIPAUX -->
        <b-row>
            <b-col cols="12">
                <b-card no-body>
                    <b-card-header class="d-flex align-items-center flex-wrap gap-2 border-bottom">
                        <h4 class="header-title mb-0 me-auto">Tableau de Bord Auditeur</h4>
                        <div class="d-flex gap-1 flex-wrap">
                            <button v-for="tab in tabs" :key="tab.id" class="btn btn-sm"
                                :class="activeTab === tab.id ? 'btn-primary' : 'btn-soft-secondary'"
                                @click="activeTab = tab.id">
                                <i :class="`ti ti-${tab.icon} me-1`"></i>{{ tab.label }}
                                <span v-if="tab.badge" class="badge bg-danger ms-1 fs-10">{{ tab.badge }}</span>
                            </button>
                        </div>
                    </b-card-header>

                    <!-- ══ OVERVIEW ══ -->
                    <template v-if="activeTab === 'overview'">
                        <b-card-body class="p-0">
                            <!-- KPIs rapides -->
                            <div class="bg-light bg-opacity-50">
                                <b-row class="text-center">
                                    <b-col cols="6" class="col-md">
                                        <p class="text-muted mb-1 mt-3">Jours Audit</p>
                                        <h4 class="mb-3"><i class="ti ti-clock text-primary me-1"></i><span class="fw-semibold">{{ stats.jours_total }}</span><small class="text-muted fs-12 ms-1">j / {{ Math.round(stats.jours_total / 5 * 10) / 10 }}s</small></h4>
                                    </b-col>
                                    <b-col cols="6" class="col-md">
                                        <p class="text-muted mb-1 mt-3">Budget Alloué</p>
                                        <h4 class="mb-3"><i class="ti ti-coin text-success me-1"></i><span class="fw-semibold">{{ formatMontant(stats.budget_total) }}</span></h4>
                                    </b-col>
                                    <b-col cols="6" class="col-md">
                                        <p class="text-muted mb-1 mt-3">Taux Réalisation</p>
                                        <h4 class="mb-3"><i class="ti ti-chart-infographic me-1"></i><span class="fw-semibold">{{ stats.taux_realisation }}%</span></h4>
                                    </b-col>
                                    <b-col cols="6" class="col-md">
                                        <p class="text-muted mb-1 mt-3">Risques Identifiés</p>
                                        <h4 class="mb-3"><i class="ti ti-alert-triangle text-danger me-1"></i><span class="fw-semibold">{{ stats.nb_risques_total }}</span></h4>
                                    </b-col>
                                </b-row>
                            </div>

                            <!-- CALENDRIER 12 MOIS -->
                            <div class="p-3 border-bottom">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <p class="text-muted fw-semibold mb-0 fs-13">
                                        <i class="ti ti-calendar-event me-1"></i>Disponibilité &amp; Charge — Exercice {{ currentYear }}
                                    </p>
                                    <small class="text-muted fs-11">
                                        <span class="badge bg-primary-subtle text-primary me-1">j</span>= jours effectifs dans le mois
                                    </small>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0 text-center align-middle" style="min-width:800px;">
                                        <thead>
                                            <tr>
                                                <th class="text-start text-muted text-uppercase fs-11 bg-light ps-2" style="min-width:110px;">Indicateur</th>
                                                <th v-for="mois in calendrier" :key="mois.mois"
                                                    class="fs-11 fw-semibold text-white p-1"
                                                    :style="`background:${moisHeaderColor(mois)};min-width:60px;`">
                                                    <div>{{ mois.label }}</div>
                                                    <div v-if="mois.jours > 0" class="fs-10 opacity-75 fw-normal">{{ mois.jours }}j</div>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td class="text-start text-muted fw-semibold fs-12 bg-light ps-2"><i class="ti ti-activity me-1"></i>Statut</td>
                                                <td v-for="mois in calendrier" :key="mois.mois" class="p-1">
                                                    <div class="rounded px-1 py-1" :style="`background:${moisBgColor(mois)};`">
                                                        <span class="fs-10 fw-semibold" :style="`color:${moisTextColor(mois)};`">
                                                            {{ mois.nb_missions > 0 ? mois.status_label : 'Libre' }}
                                                        </span>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-start text-muted fw-semibold fs-12 bg-light ps-2"><i class="ti ti-briefcase me-1 text-warning"></i>Missions</td>
                                                <td v-for="mois in calendrier" :key="mois.mois" class="fs-13 fw-bold" :class="mois.nb_missions > 0 ? 'text-warning' : 'text-muted'">
                                                    {{ mois.nb_missions > 0 ? mois.nb_missions : '—' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-start text-muted fw-semibold fs-12 bg-light ps-2"><i class="ti ti-clock me-1 text-primary"></i>Jours</td>
                                                <td v-for="mois in calendrier" :key="mois.mois" class="fs-13 fw-bold" :class="mois.jours > 0 ? 'text-primary' : 'text-muted'">
                                                    {{ mois.jours > 0 ? mois.jours : '—' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-start text-muted fw-semibold fs-12 bg-light ps-2"><i class="ti ti-circle-check me-1 text-success"></i>Dispo.</td>
                                                <td v-for="mois in calendrier" :key="mois.mois" class="p-1">
                                                    <div v-if="mois.jours === 0" class="rounded px-1 py-1 bg-success-subtle"><span class="text-success fs-10 fw-semibold">✓ Libre</span></div>
                                                    <div v-else-if="mois.jours < 10" class="rounded px-1 py-1 bg-warning-subtle"><span class="text-warning fs-10 fw-semibold">Partiel</span></div>
                                                    <div v-else class="rounded px-1 py-1 bg-danger-subtle"><span class="text-danger fs-10 fw-semibold">Occupé</span></div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-start text-muted fw-semibold fs-12 bg-light ps-2"><i class="ti ti-list me-1 text-secondary"></i>Détail</td>
                                                <td v-for="mois in calendrier" :key="mois.mois" class="p-1" style="max-width:80px;min-width:60px;">
                                                    <div v-if="mois.missions.length > 0" class="d-flex flex-column gap-1">
                                                        <div v-for="(m, mi) in mois.missions" :key="mi"
                                                            class="rounded p-1 text-start cursor-pointer"
                                                            :style="`background:${moisBgColorByStatus(m.status)};border-left:2px solid ${ganttColor(m.status)};`"
                                                            :title="`${m.libelle}${m.entity_name ? ' | '+m.entity_name : ''} — ${m.date_debut_fr} → ${m.date_fin_fr}`"
                                                            @click="openDetailById(m.id)">
                                                            <div class="fs-10 fw-semibold text-truncate" :style="`color:${ganttColor(m.status)};`">{{ m.code_mission }}</div>
                                                            <div v-if="m.entity_name" class="fs-10 text-truncate text-muted fw-medium">{{ m.entity_name }}</div>
                                                            <div v-if="m.nb_risques > 0" class="fs-9 text-danger"><i class="ti ti-alert-triangle me-1"></i>{{ m.nb_risques }}R</div>
                                                            <div class="fs-9 fw-semibold" :style="`color:${ganttColor(m.status)};`">{{ m.jours_dans_mois }}j</div>
                                                        </div>
                                                    </div>
                                                    <span v-else class="text-muted fs-11">—</span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="d-flex gap-3 mt-3 flex-wrap align-items-center">
                                    <span class="text-muted fs-11 fw-semibold">Légende :</span>
                                    <div v-for="leg in legendeCalendrier" :key="leg.label" class="d-flex align-items-center gap-1">
                                        <div class="rounded" :style="`width:12px;height:12px;background:${leg.color};`"></div>
                                        <small class="text-muted fs-11">{{ leg.label }}</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Missions en vue -->
                            <div class="p-3" v-if="missionEnCours || prochaineMission">
                                <p class="text-muted fw-semibold mb-3 fs-13"><i class="ti ti-eye me-1"></i>Missions en vue</p>
                                <b-row class="g-3">
                                    <b-col md="6" v-if="missionEnCours">
                                        <div class="border rounded p-3" style="border-left:4px solid #1E40AF !important;">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <b-badge variant="null" class="bg-primary-subtle text-primary mb-1"><i class="ti ti-player-play me-1"></i>En cours</b-badge>
                                                    <h6 class="mb-0 fw-semibold">{{ missionEnCours.libelle }}</h6>
                                                    <small class="text-muted">{{ missionEnCours.code_mission }}</small>
                                                </div>
                                                <span class="badge bg-primary text-white">{{ missionEnCours.progression }}%</span>
                                            </div>
                                            <div class="progress mb-2" style="height:6px;">
                                                <div class="progress-bar bg-primary" :style="`width:${missionEnCours.progression}%`"></div>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <small class="text-muted">{{ missionEnCours.date_debut_fr }} → {{ missionEnCours.date_fin_fr }}</small>
                                                <button class="btn btn-outline-primary btn-sm px-2 py-0" @click="openDetail(missionEnCours)"><i class="ti ti-eye"></i></button>
                                            </div>
                                        </div>
                                    </b-col>
                                    <b-col md="6" v-if="prochaineMission">
                                        <div class="border rounded p-3" style="border-left:4px solid #D97706 !important;">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <b-badge variant="null" class="bg-warning-subtle text-warning mb-1"><i class="ti ti-clock me-1"></i>Prochaine</b-badge>
                                                    <h6 class="mb-0 fw-semibold">{{ prochaineMission.libelle }}</h6>
                                                    <small class="text-muted">{{ prochaineMission.code_mission }}</small>
                                                </div>
                                                <span class="badge bg-warning-subtle text-warning">J-{{ countdownDays(prochaineMission) }}</span>
                                            </div>
                                            <small class="text-muted d-block mt-2"><i class="ti ti-calendar me-1"></i>Début le {{ prochaineMission.date_debut_fr }}</small>
                                            <button v-if="canStart(prochaineMission)" class="btn btn-success btn-sm w-100 mt-2" @click="startMission(prochaineMission)">
                                                <i class="ti ti-player-play me-1"></i>Commencer la mission
                                            </button>
                                        </div>
                                    </b-col>
                                </b-row>
                            </div>
                        </b-card-body>
                    </template>

                    <!-- ══ MES MISSIONS ══ -->
                    <template v-if="activeTab === 'missions'">
                        <b-card-header class="d-flex align-items-center flex-wrap gap-2 border-bottom border-top-0 border-dashed">
                            <div class="search-bar flex-grow-1" style="max-width:280px;">
                                <b-form-input v-model="searchMission" size="sm" class="search" placeholder="Rechercher mission, entité, code…" />
                            </div>
                            <b-form-select size="sm" v-model="filterStatus" :options="statusOptions" style="min-width:150px;" />
                            <div class="d-flex gap-2 ms-auto flex-wrap">
                                <span class="badge bg-warning-subtle text-warning fs-11 px-2 py-1"><i class="ti ti-clock me-1"></i>{{ stats.planifiees }} planifiée(s)</span>
                                <span class="badge bg-primary-subtle text-primary fs-11 px-2 py-1"><i class="ti ti-player-play me-1"></i>{{ stats.en_cours }} en cours</span>
                                <span class="badge bg-success-subtle text-success fs-11 px-2 py-1"><i class="ti ti-check me-1"></i>{{ stats.terminees }} terminée(s)</span>
                            </div>
                            <small class="text-muted">{{ filteredMissions.length }} / {{ affectations.length }}</small>
                        </b-card-header>

                        <b-card-body class="p-3">
                            <div v-if="filteredMissions.length === 0" class="text-center text-muted py-5">
                                <i class="ti ti-inbox fs-40 d-block mb-3 text-muted"></i>
                                <h5 class="text-muted">Aucune mission trouvée</h5>
                            </div>

                            <div class="d-flex flex-column gap-3">
                                <div v-for="aff in filteredMissions" :key="aff.id"
                                    class="border rounded-3 overflow-hidden shadow-sm"
                                    :style="`border-left:5px solid ${ganttColor(aff.status)} !important;`">

                                    <!-- EN-TÊTE CARTE -->
                                    <div class="p-3 pb-2 d-flex align-items-start justify-content-between flex-wrap gap-2"
                                        :style="`background:${moisBgColorByStatus(aff.status)}40;`">
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                                <span v-if="canStart(aff)" class="badge bg-success text-white fs-11"><i class="ti ti-player-play me-1"></i>Démarre aujourd'hui</span>
                                                <span class="badge bg-dark text-white fs-12 fw-bold">{{ aff.code_mission }}</span>
                                                <b-badge :variant="null" class="px-2 py-1" :class="statusBadgeClass(aff.status)">{{ statusLabel(aff.status) }}</b-badge>
                                                <b-badge :variant="null" class="px-2 py-1" :class="roleBadgeClass(aff.mon_role)"><i class="ti ti-user-check me-1"></i>{{ aff.mon_role || '—' }}</b-badge>
                                                <span v-if="aff.processus?.nom" class="badge bg-info-subtle text-info fs-11"><i class="ti ti-sitemap me-1"></i>{{ aff.processus.nom }}</span>
                                                <span v-if="aff.nb_risques > 0" class="badge bg-danger-subtle text-danger fs-11"><i class="ti ti-alert-triangle me-1"></i>{{ aff.nb_risques }} risque(s)</span>
                                            </div>
                                            <h5 class="mb-0 fw-semibold fs-15">{{ aff.libelle }}</h5>
                                            <p v-if="aff.objectif" class="text-muted fs-12 mb-0 mt-1 text-truncate" style="max-width:600px;"><i class="ti ti-target me-1"></i>{{ aff.objectif }}</p>
                                        </div>
                                        <div class="d-flex gap-2 align-items-center flex-shrink-0">
                                            <button v-if="canStart(aff)" class="btn btn-success btn-sm" @click="startMission(aff)"><i class="ti ti-player-play me-1"></i>Commencer</button>
                                            <button class="btn btn-outline-secondary btn-sm" @click="openDetail(aff)"><i class="ti ti-eye me-1"></i>Détail</button>
                                        </div>
                                    </div>

                                    <!-- CORPS CARTE — 4 colonnes -->
                                    <div class="p-3 pt-2">
                                        <b-row class="g-3">
                                            <!-- COL 1 : Période -->
                                            <b-col lg="3" md="6">
                                                <div class="border rounded p-3 h-100 bg-light bg-opacity-40">
                                                    <p class="text-muted fs-11 text-uppercase fw-semibold mb-3"><i class="ti ti-calendar me-1"></i>Période Globale</p>
                                                    <div class="d-flex align-items-center gap-2 mb-1">
                                                        <span class="badge bg-secondary-subtle text-secondary fs-10 fw-normal px-2">Début</span>
                                                        <span class="fw-semibold fs-13">{{ aff.date_debut_fr }}</span>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-2 mb-3">
                                                        <span class="badge bg-secondary-subtle text-secondary fs-10 fw-normal px-2">Fin</span>
                                                        <span class="fw-semibold fs-13">{{ aff.date_fin_fr }}</span>
                                                    </div>
                                                    <div class="d-flex gap-2 mb-3">
                                                        <div class="rounded px-2 py-1 bg-primary-subtle text-center flex-fill">
                                                            <div class="fw-bold text-primary fs-17">{{ aff.duree }}</div>
                                                            <div class="text-muted fs-10">jours</div>
                                                        </div>
                                                        <div class="rounded px-2 py-1 bg-info-subtle text-center flex-fill">
                                                            <div class="fw-bold text-info fs-17">{{ Math.round(aff.duree / 5 * 10) / 10 }}</div>
                                                            <div class="text-muted fs-10">semaines</div>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex justify-content-between mb-1">
                                                        <small class="text-muted fw-semibold fs-11">Avancement</small>
                                                        <small class="fw-bold fs-12" :style="`color:${ganttColor(aff.status)};`">{{ aff.progression }}%</small>
                                                    </div>
                                                    <div class="progress" style="height:8px;border-radius:4px;">
                                                        <div class="progress-bar" :class="progressBarClass(aff.status)" :style="`width:${aff.progression}%`"></div>
                                                    </div>
                                                    <div v-if="aff.lieux" class="text-muted fs-11 mt-2 border-top pt-2"><i class="ti ti-map-pin me-1 text-danger"></i>{{ aff.lieux }}</div>
                                                </div>
                                            </b-col>

                                            <!-- COL 2 : Entités -->
                                            <b-col lg="4" md="6">
                                                <div class="border rounded p-3 h-100">
                                                    <p class="text-muted fs-11 text-uppercase fw-semibold mb-3">
                                                        <i class="ti ti-building me-1"></i>Entités &amp; Périodes
                                                        <span class="badge bg-secondary-subtle text-secondary ms-1 fs-10">{{ affEntitiesForMission(aff).length }}</span>
                                                    </p>
                                                    <div v-if="affEntitiesForMission(aff).length === 0" class="text-center text-muted fs-12 py-3">
                                                        <i class="ti ti-building-off d-block fs-22 mb-1"></i>Entités non spécifiées
                                                    </div>
                                                    <div v-for="(ae, aei) in affEntitiesForMission(aff)" :key="aei"
                                                        class="rounded p-2 mb-2"
                                                        :style="`background:${ganttColor(aff.status)}0D;border-left:3px solid ${ganttColor(aff.status)};`">
                                                        <div class="d-flex align-items-start justify-content-between gap-2">
                                                            <div class="flex-grow-1">
                                                                <div class="fw-semibold fs-13 d-flex align-items-center gap-1 mb-1">
                                                                    <i class="ti ti-building fs-12" :style="`color:${ganttColor(aff.status)};`"></i>{{ ae.entity_name || '—' }}
                                                                </div>
                                                                <div class="d-flex align-items-center gap-1 text-muted fs-11">
                                                                    <span>{{ ae.entity_date_debut_fr }}</span><i class="ti ti-arrow-right fs-10"></i><span>{{ ae.entity_date_fin_fr }}</span>
                                                                </div>
                                                            </div>
                                                            <div class="badge fs-12 fw-bold px-2 py-1" :style="`background:${ganttColor(aff.status)}22;color:${ganttColor(aff.status)};`">{{ entityDuration(ae) }}j</div>
                                                        </div>
                                                        <!-- Mini Gantt -->
                                                        <div class="d-flex mt-2" style="gap:2px;">
                                                            <div v-for="m in 12" :key="m" class="flex-fill rounded-1" style="height:4px;"
                                                                :style="`background:${inRangeEntity(ae, m) ? ganttColor(aff.status) : '#E2E8F0'};`"></div>
                                                        </div>
                                                        <div class="d-flex justify-content-between mt-1">
                                                            <small class="text-muted fs-10">Jan</small>
                                                            <small class="text-muted fs-10">Déc</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </b-col>

                                            <!-- COL 3 : Équipe -->
                                            <b-col lg="3" md="6">
                                                <div class="border rounded p-3 h-100">
                                                    <p class="text-muted fs-11 text-uppercase fw-semibold mb-3">
                                                        <i class="ti ti-users me-1"></i>Équipe Mission
                                                        <span class="badge bg-secondary-subtle text-secondary ms-1 fs-10">{{ equipesParMission[aff.mission_id]?.total ?? 0 }}</span>
                                                    </p>
                                                    <div v-if="!equipesParMission[aff.mission_id]?.membres?.length" class="text-center text-muted fs-12 py-3">
                                                        <i class="ti ti-user-off d-block fs-22 mb-1"></i>Non affectée
                                                    </div>
                                                    <div v-for="(m, mi) in equipesParMission[aff.mission_id]?.membres ?? []" :key="mi"
                                                        class="d-flex align-items-center gap-2 py-2"
                                                        :class="[mi < (equipesParMission[aff.mission_id]?.membres?.length ?? 0) - 1 ? 'border-bottom' : '', m.is_me ? 'rounded px-1' : '']"
                                                        :style="m.is_me ? 'background:#1E40AF0D;' : ''">
                                                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                                            style="width:32px;height:32px;font-size:11px;font-weight:700;"
                                                            :class="m.is_me ? 'bg-primary text-white' : 'bg-light border text-dark'">
                                                            {{ (m.last_name?.[0] ?? '') + (m.first_name?.[0] ?? '') }}
                                                        </div>
                                                        <div class="flex-grow-1 overflow-hidden">
                                                            <div class="fs-12 fw-semibold text-truncate">
                                                                {{ m.last_name }} {{ m.first_name }}
                                                                <span v-if="m.is_me" class="badge bg-primary text-white fs-9 ms-1 px-1">Moi</span>
                                                            </div>
                                                            <div class="d-flex align-items-center gap-1 mt-1">
                                                                <b-badge :variant="null" :class="roleBadgeClass(m.role)" class="fs-10">{{ m.role || '—' }}</b-badge>
                                                                <small class="text-muted fs-10">{{ m.audit_code }}</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </b-col>

                                            <!-- COL 4 : Budget -->
                                            <b-col lg="2" md="6">
                                                <div class="border rounded p-3 h-100">
                                                    <p class="text-muted fs-11 text-uppercase fw-semibold mb-2"><i class="ti ti-coin me-1"></i>Budget Alloué</p>
                                                    <div class="text-center py-2 mb-2 rounded" :style="`background:${(aff.budget_individuel ?? 0) > 0 ? '#05966915' : '#94A3B815'};`">
                                                        <div class="fw-bold text-success fs-20 lh-1">{{ formatMontantCourt(aff.budget_individuel ?? 0) }}</div>
                                                        <small class="text-muted fs-10">FCFA</small>
                                                    </div>
                                                    <div v-if="(budgetLignes[aff.id] ?? []).length > 0" class="border-top pt-2">
                                                        <div v-for="(ligne, li) in budgetLignes[aff.id]" :key="li"
                                                            class="mb-1 pb-1" :class="li < budgetLignes[aff.id].length - 1 ? 'border-bottom' : ''">
                                                            <div class="d-flex justify-content-between align-items-start">
                                                                <div class="flex-grow-1 overflow-hidden me-1">
                                                                    <div class="fs-11 text-muted text-truncate" :title="ligne.libelle">{{ ligne.libelle }}</div>
                                                                    <div v-if="ligne.entity_name && ligne.entity_name !== '—'" class="fs-10 text-muted opacity-75"><i class="ti ti-building fs-9 me-1"></i>{{ ligne.entity_name }}</div>
                                                                </div>
                                                                <span class="fs-11 fw-semibold text-success flex-shrink-0">{{ formatMontantCourt(ligne.montant) }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div v-else class="text-muted fs-11 text-center pt-1 border-top"><i class="ti ti-coins-off fs-13 me-1"></i>Aucune ligne</div>
                                                </div>
                                            </b-col>
                                        </b-row>
                                    </div>
                                </div>
                            </div>
                        </b-card-body>

                        <b-card-footer class="border-top border-light d-flex align-items-center justify-content-between">
                            <div class="text-muted fs-13"><span class="fw-semibold text-body">{{ filteredMissions.length }}</span> mission(s) — <span class="fw-semibold">{{ stats.jours_total }}</span> jours</div>
                            <div class="text-muted fs-12">Budget : <strong>{{ formatMontantCourt(stats.budget_total) }}</strong></div>
                        </b-card-footer>
                    </template>

                    <!-- ══ GANTT PLANNING ══ -->
                    <template v-if="activeTab === 'calendrier'">
                        <b-card-body class="p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-nowrap mb-0" style="min-width:920px;">
                                    <thead class="bg-light bg-opacity-50">
                                        <!-- Ligne 1 : En-têtes mois colorés -->
                                        <tr>
                                            <th class="text-muted text-uppercase fs-11 ps-3 border-end" style="min-width:180px;">Mission</th>
                                            <th class="text-muted text-uppercase fs-11" style="min-width:110px;">Entité</th>
                                            <th class="text-muted text-uppercase fs-11 text-center" style="min-width:40px;">Rôle</th>
                                            <th v-for="mois in calendrier" :key="mois.mois"
                                                class="text-center text-white fs-11 fw-semibold p-1"
                                                :style="`background:${moisHeaderColor(mois)};min-width:56px;`">
                                                <div>{{ mois.label }}</div>
                                                <div v-if="mois.jours > 0" class="fs-10 opacity-75 fw-normal">{{ mois.jours }}j</div>
                                            </th>
                                        </tr>
                                        <!-- Ligne 2 : nb missions par mois -->
                                        <tr class="bg-light">
                                            <td class="ps-3 text-muted fs-11 py-1" colspan="3">Missions / mois</td>
                                            <td v-for="mois in calendrier" :key="mois.mois"
                                                class="text-center py-1 fs-11 fw-semibold"
                                                :class="mois.nb_missions > 0 ? 'text-primary' : 'text-muted'">
                                                {{ mois.nb_missions > 0 ? mois.nb_missions : '' }}
                                            </td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Groupes par mission avec ligne header + lignes entités -->
                                        <template v-for="(group, gi) in ganttGroupes" :key="`g-${gi}`">
                                            <!-- LIGNE HEADER MISSION -->
                                            <tr style="background:#EEF2FF;">
                                                <td class="ps-3 py-2" colspan="2">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="badge bg-dark text-white fs-11">{{ group.code_mission }}</span>
                                                        <b-badge :variant="null" :class="statusBadgeClass(group.status)" class="fs-10">{{ statusLabel(group.status) }}</b-badge>
                                                        <span v-if="group.nb_risques > 0" class="badge bg-danger-subtle text-danger fs-10"><i class="ti ti-alert-triangle me-1"></i>{{ group.nb_risques }}R</span>
                                                    </div>
                                                    <div class="fw-semibold fs-13 mt-1 text-truncate" style="max-width:280px;" :title="group.libelle">{{ group.libelle }}</div>
                                                    <div class="d-flex gap-2 mt-1 flex-wrap">
                                                        <small class="text-muted fs-10"><i class="ti ti-calendar me-1"></i>{{ group.date_debut_fr }} → {{ group.date_fin_fr }}</small>
                                                        <small class="text-muted fs-10" v-if="group.lieux"><i class="ti ti-map-pin me-1"></i>{{ group.lieux }}</small>
                                                        <small class="text-info fs-10" v-if="group.processus_nom"><i class="ti ti-sitemap me-1"></i>{{ group.processus_nom }}</small>
                                                    </div>
                                                </td>
                                                <td class="text-center py-2">
                                                    <div class="progress" style="height:6px;min-width:40px;">
                                                        <div class="progress-bar" :class="progressBarClass(group.status)" :style="`width:${group.progression}%`"></div>
                                                    </div>
                                                    <small class="text-muted fs-10">{{ group.progression }}%</small>
                                                </td>
                                                <!-- Barres globales de la mission (fond léger) -->
                                                <td v-for="mois in calendrier" :key="mois.mois" class="p-0" style="height:44px;">
                                                    <div v-if="inRangeGlobal(group, mois.mois)"
                                                        class="h-100"
                                                        :style="`background:${ganttColor(group.status)}18;`">
                                                    </div>
                                                </td>
                                            </tr>

                                            <!-- LIGNES ENTITÉS -->
                                            <tr v-for="ae in group.entities" :key="`ae-${ae.id}-${ae.entity_id}`"
                                                class="cursor-pointer"
                                                :class="{ 'table-hover': true }"
                                                @click="openDetail(ae)">
                                                <td class="ps-4 py-1">
                                                    <small class="text-muted">└</small>
                                                    <small class="text-muted fs-11 ms-1">{{ ae.mon_role }}</small>
                                                </td>
                                                <td class="py-1">
                                                    <div v-if="ae.entity_name" class="fw-medium fs-12">{{ ae.entity_name }}</div>
                                                    <small class="text-muted d-block fs-10">{{ ae.entity_date_debut_fr }} → {{ ae.entity_date_fin_fr }}</small>
                                                    <small class="text-muted d-block fs-10">{{ entityDuration(ae) }}j</small>
                                                </td>
                                                <td class="text-center py-1">
                                                    <b-badge :variant="null" :class="roleBadgeClass(ae.mon_role)" class="fs-10">{{ ae.mon_role || '—' }}</b-badge>
                                                </td>
                                                <!-- Barres Gantt entité -->
                                                <td v-for="mois in calendrier" :key="mois.mois" class="p-0" style="height:36px;">
                                                    <div v-if="inRangeEntity(ae, mois.mois)"
                                                        class="h-100 d-flex align-items-center px-1"
                                                        :style="`background:${ganttColor(ae.status)};`"
                                                        :title="`${ae.entity_name || ae.libelle} — ${ae.entity_date_debut_fr} → ${ae.entity_date_fin_fr}`">
                                                        <div v-if="isStartMonthEntity(ae, mois.mois)" class="text-white" style="line-height:1.1;">
                                                            <div class="fs-10 fw-semibold text-truncate" style="max-width:50px;">{{ ae.mon_role }}</div>
                                                            <div v-if="ae.entity_name" class="fs-9 opacity-80 text-truncate" style="max-width:50px;">{{ ae.entity_name }}</div>
                                                        </div>
                                                        <div v-if="isEndMonthEntity(ae, mois.mois) && !isStartMonthEntity(ae, mois.mois)" class="ms-auto">
                                                            <div class="rounded-circle bg-white" style="width:6px;height:6px;"></div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>

                                            <!-- Séparateur -->
                                            <tr><td :colspan="3 + 12" style="height:4px;background:#E2E8F0;padding:0;"></td></tr>
                                        </template>

                                        <tr v-if="affectationEntities.length === 0">
                                            <td :colspan="3 + 12" class="text-center text-muted py-5">
                                                <i class="ti ti-calendar-off fs-32 d-block mb-2"></i>Aucune mission planifiée
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="p-3 border-top d-flex gap-3 flex-wrap align-items-center">
                                <span class="text-muted fs-12 me-1 fw-semibold">Légende :</span>
                                <div v-for="leg in legendeStatuts" :key="leg.status" class="d-flex align-items-center gap-1">
                                    <div class="rounded" :style="`width:16px;height:8px;background:${leg.color};`"></div>
                                    <small class="text-muted fs-11">{{ leg.label }}</small>
                                </div>
                                <span class="text-muted fs-11 ms-auto">Cliquer sur une ligne pour voir le détail</span>
                            </div>
                        </b-card-body>
                    </template>

                    <!-- ══ BUDGET ══ -->
                    <template v-if="activeTab === 'budget'">
                        <b-card-body class="p-0">
                            <div class="bg-light bg-opacity-50">
                                <b-row class="text-center">
                                    <b-col cols="12" md="4">
                                        <p class="text-muted mb-1 mt-3">Budget Total</p>
                                        <h4 class="mb-3"><i class="ti ti-coin text-success me-1"></i><span class="fw-semibold">{{ formatMontant(stats.budget_total) }}</span></h4>
                                    </b-col>
                                    <b-col cols="6" md="4">
                                        <p class="text-muted mb-1 mt-3">Missions avec budget</p>
                                        <h4 class="mb-3"><span class="fw-semibold">{{ affectationsAvecBudget }}</span></h4>
                                    </b-col>
                                    <b-col cols="6" md="4">
                                        <p class="text-muted mb-1 mt-3">Moyenne / Mission</p>
                                        <h4 class="mb-3"><span class="fw-semibold">{{ formatMontant(budgetMoyen) }}</span></h4>
                                    </b-col>
                                </b-row>
                            </div>
                            <div class="table-card">
                                <b-table-simple responsive hover borderless class="table-custom table-nowrap mb-0 align-middle">
                                    <b-thead class="bg-light thead-sm bg-opacity-50">
                                        <b-tr class="text-uppercase fs-12">
                                            <b-th class="text-muted">#</b-th>
                                            <b-th class="text-muted">Mission</b-th>
                                            <b-th class="text-muted">Entités</b-th>
                                            <b-th class="text-muted">Rôle</b-th>
                                            <b-th class="text-muted">Lignes</b-th>
                                            <b-th class="text-muted text-end">Budget</b-th>
                                        </b-tr>
                                    </b-thead>
                                    <b-tbody>
                                        <b-tr v-for="(aff, idx) in affectations" :key="aff.id">
                                            <b-td class="text-muted fw-medium">#{{ idx + 1 }}</b-td>
                                            <b-td>
                                                <div class="fw-medium">{{ aff.code_mission }}</div>
                                                <small class="text-muted">{{ truncate(aff.libelle, 30) }}</small>
                                            </b-td>
                                            <b-td><small class="text-muted">{{ truncate(aff.entities_list, 25) }}</small></b-td>
                                            <b-td><b-badge :variant="null" :class="roleBadgeClass(aff.mon_role)">{{ aff.mon_role || '—' }}</b-badge></b-td>
                                            <b-td>
                                                <div v-for="(ligne, li) in (budgetLignes[aff.id] ?? [])" :key="li" class="d-flex justify-content-between fs-12 text-muted">
                                                    <span>{{ ligne.libelle }}<small v-if="ligne.entity_name" class="ms-1 text-muted">({{ ligne.entity_name }})</small></span>
                                                    <span class="fw-semibold text-success ms-3">{{ formatMontant(ligne.montant) }}</span>
                                                </div>
                                                <span v-if="!(budgetLignes[aff.id] ?? []).length" class="text-muted fs-12">—</span>
                                            </b-td>
                                            <b-td class="text-end">
                                                <strong class="text-success">{{ formatMontant(aff.budget_individuel ?? 0) }}</strong>
                                                <div class="text-muted fs-11">FCFA</div>
                                            </b-td>
                                        </b-tr>
                                    </b-tbody>
                                </b-table-simple>
                            </div>
                        </b-card-body>
                    </template>

                </b-card>
            </b-col>
        </b-row>

        <!-- OFFCANVAS DÉTAIL MISSION -->
        <Teleport to="body">
            <div v-if="selectedMission" class="offcanvas offcanvas-end show" tabindex="-1" style="width:580px;z-index:1055;">
                <!-- HEADER avec gradient -->
                <div class="offcanvas-header pb-2"
                    :style="`background:linear-gradient(135deg,${ganttColor(selectedMission.status)}15,${ganttColor(selectedMission.status)}05);border-left:5px solid ${ganttColor(selectedMission.status)};`">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                            <span class="badge bg-dark text-white fw-bold">{{ selectedMission.code_mission }}</span>
                            <b-badge :variant="null" :class="statusBadgeClass(selectedMission.status)">{{ statusLabel(selectedMission.status) }}</b-badge>
                            <b-badge :variant="null" :class="roleBadgeClass(selectedMission.mon_role)">{{ selectedMission.mon_role || '—' }}</b-badge>
                            <span v-if="selectedMission.type_mission" class="badge bg-light text-muted fs-10">{{ selectedMission.type_mission }}</span>
                        </div>
                        <h5 class="offcanvas-title mb-0 fs-15">{{ selectedMission.libelle }}</h5>
                        <p v-if="selectedMission.objectif" class="text-muted fs-12 mb-0 mt-1"><i class="ti ti-target me-1"></i>{{ selectedMission.objectif }}</p>
                        <!-- Progress bar -->
                        <div class="mt-2">
                            <div class="d-flex justify-content-between mb-1">
                                <small class="text-muted fs-11">Avancement</small>
                                <small class="fw-bold fs-11" :style="`color:${ganttColor(selectedMission.status)};`">{{ selectedMission.progression }}%</small>
                            </div>
                            <div class="progress" style="height:5px;">
                                <div class="progress-bar" :class="progressBarClass(selectedMission.status)" :style="`width:${selectedMission.progression}%`"></div>
                            </div>
                        </div>
                    </div>
                    <button class="btn-close ms-2 flex-shrink-0" @click="selectedMission = null"></button>
                </div>

                <!-- Alerte démarrage -->
                <div v-if="canStart(selectedMission)" class="alert alert-success border-0 rounded-0 mb-0 d-flex align-items-center gap-2 p-2">
                    <i class="ti ti-player-play fs-16"></i>
                    <div class="flex-grow-1 fs-12">La date de début est atteinte. Vous pouvez démarrer.</div>
                    <button class="btn btn-success btn-sm px-2 py-1" @click="startMission(selectedMission)"><i class="ti ti-player-play me-1"></i>Commencer</button>
                </div>

                <!-- Sub-nav 4 onglets -->
                <div class="border-bottom px-3 pt-2">
                    <div class="d-flex gap-1">
                        <button v-for="dt in detailTabs" :key="dt.id" class="btn btn-sm px-3 py-1 border-0 rounded-0"
                            :class="detailTab === dt.id ? 'border-bottom border-2 border-primary text-primary fw-semibold' : 'text-muted'"
                            :style="detailTab === dt.id ? 'border-bottom:2px solid currentColor !important;' : ''"
                            @click="detailTab = dt.id">
                            <i :class="`ti ti-${dt.icon} me-1`"></i>{{ dt.label }}
                            <span v-if="dt.id === 'risques' && selectedMissionRisques.length > 0" class="badge bg-danger ms-1 fs-10">{{ selectedMissionRisques.length }}</span>
                        </button>
                    </div>
                </div>

                <div class="offcanvas-body p-0 overflow-auto">

                    <!-- TAB : INFOS -->
                    <div v-if="detailTab === 'infos'" class="p-3 d-flex flex-column gap-3">
                        <!-- Période globale -->
                        <div class="border rounded p-3 bg-light bg-opacity-50">
                            <p class="text-muted fs-11 text-uppercase fw-semibold mb-2"><i class="ti ti-calendar me-1"></i>Période Globale</p>
                            <div class="row g-2">
                                <div class="col-4"><label class="text-muted fs-11">Début</label><p class="mb-0 fw-semibold fs-13">{{ selectedMission.date_debut_fr }}</p></div>
                                <div class="col-4"><label class="text-muted fs-11">Fin</label><p class="mb-0 fw-semibold fs-13">{{ selectedMission.date_fin_fr }}</p></div>
                                <div class="col-4"><label class="text-muted fs-11">Durée</label><p class="mb-0 fw-semibold fs-13">{{ selectedMission.duree }}j · {{ Math.round((selectedMission.duree ?? 0) / 5 * 10) / 10 }}s</p></div>
                            </div>
                            <div v-if="selectedMission.lieux" class="text-muted fs-12 mt-2"><i class="ti ti-map-pin me-1"></i>{{ selectedMission.lieux }}</div>
                        </div>

                        <!-- Processus -->
                        <div v-if="selectedMission.processus_nom" class="border rounded p-3">
                            <p class="text-muted fs-11 text-uppercase fw-semibold mb-2"><i class="ti ti-sitemap me-1"></i>Processus Audité</p>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-info-subtle text-info fs-12 px-2">{{ selectedMission.processus_code }}</span>
                                <span class="fw-semibold fs-14">{{ selectedMission.processus_nom }}</span>
                            </div>
                            <p v-if="selectedMission.processus_description" class="text-muted fs-12 mt-2 mb-0">{{ selectedMission.processus_description }}</p>
                        </div>

                        <!-- Description -->
                        <div v-if="selectedMission.description" class="border rounded p-3">
                            <p class="text-muted fs-11 text-uppercase fw-semibold mb-2"><i class="ti ti-file-text me-1"></i>Description</p>
                            <p class="text-muted fs-12 mb-0">{{ selectedMission.description }}</p>
                        </div>

                        <!-- Entités -->
                        <div class="border rounded p-3">
                            <p class="text-muted fs-11 text-uppercase fw-semibold mb-2">
                                <i class="ti ti-building me-1"></i>Entités Affectées
                                <span class="badge bg-secondary-subtle text-secondary ms-1">{{ selectedMissionEntities.length }}</span>
                            </p>
                            <div v-if="selectedMissionEntities.length === 0" class="text-muted fs-12 text-center py-2"><i class="ti ti-building-off me-1"></i>Aucune entité spécifiée</div>
                            <div v-for="(ae, i) in selectedMissionEntities" :key="i"
                                class="d-flex gap-3 align-items-start py-2" :class="i < selectedMissionEntities.length - 1 ? 'border-bottom' : ''">
                                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                    style="width:36px;height:36px;" :style="`background:${ganttColor(selectedMission.status)}22;`">
                                    <i class="ti ti-building fs-16" :style="`color:${ganttColor(selectedMission.status)};`"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold fs-14">{{ ae.entity_name }}</div>
                                    <div class="text-muted fs-12"><i class="ti ti-calendar-event me-1"></i>{{ ae.entity_date_debut_fr }} → {{ ae.entity_date_fin_fr }}</div>
                                    <div class="fs-12 mt-1">
                                        <span class="badge px-2 py-1" :style="`background:${ganttColor(selectedMission.status)}22;color:${ganttColor(selectedMission.status)};`">
                                            {{ entityDuration(ae) }}j · {{ Math.round(entityDuration(ae) / 5 * 10) / 10 }}s
                                        </span>
                                    </div>
                                    <!-- Mini Gantt entité -->
                                    <div class="d-flex mt-2" style="gap:2px;">
                                        <div v-for="m in 12" :key="m" class="flex-fill rounded-1" style="height:4px;"
                                            :style="`background:${inRangeEntity(ae, m) ? ganttColor(selectedMission.status) : '#E2E8F0'};`"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Budget -->
                        <div class="border rounded p-3" v-if="(budgetLignes[selectedMission.id] ?? []).length">
                            <p class="text-muted fs-11 text-uppercase fw-semibold mb-2"><i class="ti ti-coin me-1"></i>Lignes Budgétaires</p>
                            <div v-for="(ligne, li) in budgetLignes[selectedMission.id]" :key="li"
                                class="d-flex justify-content-between align-items-start py-2 border-bottom fs-13">
                                <div>
                                    <div>{{ ligne.libelle }}</div>
                                    <small v-if="ligne.entity_name" class="text-muted">{{ ligne.entity_name }}</small>
                                </div>
                                <strong class="text-success flex-shrink-0 ms-2">{{ formatMontant(ligne.montant) }}</strong>
                            </div>
                            <div class="d-flex justify-content-between pt-2 fw-semibold fs-14 mt-1">
                                <span>Total</span>
                                <strong class="text-success">{{ formatMontant(selectedMission.budget_individuel ?? 0) }}</strong>
                            </div>
                        </div>
                    </div>

                    <!-- TAB : ÉQUIPE -->
                    <div v-if="detailTab === 'equipe'" class="p-3">
                        <div v-if="!equipesParMission[selectedMission.mission_id]?.membres?.length" class="text-center text-muted py-5">
                            <i class="ti ti-user-off fs-40 d-block mb-2"></i>Aucun membre assigné
                        </div>
                        <div v-for="(m, mi) in equipesParMission[selectedMission.mission_id]?.membres ?? []" :key="mi"
                            class="d-flex align-items-center gap-3 p-3 border rounded mb-2"
                            :style="m.is_me ? 'background:#1E40AF06;border-color:#1E40AF44 !important;' : ''">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                style="width:42px;height:42px;font-size:14px;font-weight:700;"
                                :class="m.is_me ? 'bg-primary text-white' : 'bg-light border text-dark'">
                                {{ (m.last_name?.[0] ?? '') + (m.first_name?.[0] ?? '') }}
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold fs-14">{{ m.last_name }} {{ m.first_name }}<span v-if="m.is_me" class="badge bg-primary-subtle text-primary fs-10 ms-2">Moi</span></div>
                                <small class="text-muted">{{ m.audit_code }}</small>
                            </div>
                            <b-badge :variant="null" :class="roleBadgeClass(m.role)" class="fs-11 px-2">{{ m.role_libelle || m.role || '—' }}</b-badge>
                        </div>
                    </div>

                    <!-- TAB : RISQUES -->
                    <div v-if="detailTab === 'risques'" class="p-3">
                        <div v-if="selectedMissionRisques.length === 0" class="text-center text-muted py-5">
                            <i class="ti ti-shield-check fs-40 d-block mb-2 text-success"></i>
                            <h6 class="text-success">Aucun risque identifié</h6>
                            <p class="fs-12">Aucun risque n'a été enregistré pour cette mission.</p>
                        </div>
                        <div v-for="(risque, ri) in selectedMissionRisques" :key="ri"
                            class="border rounded p-3 mb-3"
                            :style="`border-left:4px solid ${niveauColor(risque.niveau)} !important;`">
                            <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded d-flex align-items-center justify-content-center" style="width:34px;height:34px;"
                                        :style="`background:${niveauColor(risque.niveau)}20;`">
                                        <i class="ti ti-alert-triangle fs-18" :style="`color:${niveauColor(risque.niveau)};`"></i>
                                    </div>
                                    <div>
                                        <div class="fw-semibold fs-13">{{ risque.titre }}</div>
                                        <span class="badge fs-10 px-2" :style="`background:${niveauColor(risque.niveau)}20;color:${niveauColor(risque.niveau)};`">{{ risque.niveau || 'Non défini' }}</span>
                                    </div>
                                </div>
                                <span v-if="risque.statut" class="badge bg-light text-muted fs-10">{{ risque.statut }}</span>
                            </div>
                            <div v-if="risque.description" class="text-muted fs-12 mb-2">{{ risque.description }}</div>
                            <div v-if="risque.probabilite || risque.impact" class="d-flex gap-3 mb-2">
                                <div v-if="risque.probabilite" class="fs-12"><span class="text-muted">Probabilité :</span> <strong>{{ risque.probabilite }}</strong></div>
                                <div v-if="risque.impact" class="fs-12"><span class="text-muted">Impact :</span> <strong>{{ risque.impact }}</strong></div>
                            </div>
                            <div v-if="risque.mesures" class="rounded p-2 fs-12" style="background:#F0FDF4;">
                                <i class="ti ti-shield me-1 text-success"></i><strong class="text-success">Mesures :</strong> {{ risque.mesures }}
                            </div>
                        </div>
                        <!-- Alerte si risques élevés -->
                        <div v-if="risquesEleves.length > 0" class="alert alert-danger border-0 d-flex gap-2 mt-2">
                            <i class="ti ti-alert-triangle fs-20 flex-shrink-0"></i>
                            <div class="fs-12">
                                <strong>{{ risquesEleves.length }} risque(s) de niveau élevé/critique.</strong>
                                Une attention particulière est requise pour cette mission.
                            </div>
                        </div>
                    </div>

                    <!-- TAB : GUIDE -->
                    <div v-if="detailTab === 'guide'" class="p-3 d-flex flex-column gap-3">
                        <!-- Checklist démarrage -->
                        <div class="border rounded p-3">
                            <p class="text-muted fs-11 text-uppercase fw-semibold mb-3"><i class="ti ti-checklist me-1"></i>Checklist de Démarrage</p>
                            <div v-for="(item, ci) in checklistDemarrage" :key="ci" class="d-flex align-items-center gap-2 py-2"
                                :class="ci < checklistDemarrage.length - 1 ? 'border-bottom' : ''">
                                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                    style="width:24px;height:24px;"
                                    :class="item.done ? 'bg-success' : 'bg-light border'">
                                    <i :class="item.done ? 'ti ti-check text-white fs-12' : 'ti ti-circle text-muted fs-12'"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fs-13 fw-medium" :class="item.done ? 'text-success' : 'text-body'">{{ item.label }}</div>
                                    <small v-if="item.desc" class="text-muted fs-11">{{ item.desc }}</small>
                                </div>
                            </div>
                        </div>

                        <!-- Phases mission -->
                        <div class="border rounded p-3">
                            <p class="text-muted fs-11 text-uppercase fw-semibold mb-3"><i class="ti ti-list-numbers me-1"></i>Étapes Clés</p>
                            <div class="d-flex flex-column gap-1">
                                <div v-for="(phase, pi) in phasesMission" :key="pi" class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 fw-bold fs-12"
                                        style="width:28px;height:28px;"
                                        :class="phase.done ? 'bg-success text-white' : (phase.current ? 'bg-primary text-white' : 'bg-light border text-muted')">
                                        {{ phase.done ? '✓' : (pi + 1) }}
                                    </div>
                                    <div>
                                        <div class="fs-13" :class="phase.done ? 'text-success' : (phase.current ? 'text-primary fw-semibold' : 'text-muted')">{{ phase.label }}</div>
                                        <small v-if="phase.current" class="text-primary fs-11">← En cours</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Rappels réglementaires -->
                        <div class="alert alert-warning border-0 rounded p-3">
                            <p class="fw-semibold fs-12 mb-2"><i class="ti ti-scale me-1"></i>Rappels Réglementaires</p>
                            <ul class="mb-0 fs-12 ps-3">
                                <li>Respecter le programme de travail approuvé</li>
                                <li>Documenter toutes les observations avec preuves</li>
                                <li>Respecter la confidentialité des informations</li>
                                <li>Signaler tout conflit d'intérêt potentiel</li>
                                <li>Respecter les délais de restitution</li>
                            </ul>
                        </div>
                    </div>

                </div>
            </div>
            <div v-if="selectedMission" class="offcanvas-backdrop fade show" @click="selectedMission = null"></div>
        </Teleport>

        <!-- TOAST -->
        <Teleport to="body">
            <div v-if="toast.show" class="position-fixed bottom-0 end-0 p-3" style="z-index:9999;">
                <div class="toast show align-items-center border-0 shadow-lg"
                    :class="toast.type === 'success' ? 'bg-success text-white' : 'bg-danger text-white'">
                    <div class="d-flex">
                        <div class="toast-body d-flex align-items-center gap-2">
                            <i :class="`ti ti-${toast.type === 'success' ? 'check' : 'x'} fs-16`"></i>
                            {{ toast.message }}
                        </div>
                        <button class="btn-close btn-close-white me-2 m-auto" @click="toast.show = false"></button>
                    </div>
                </div>
            </div>
        </Teleport>
    </VerticalLayout>
</template>

<script setup lang="ts">
import PageTitle from '@/components/PageTitle.vue';
import VerticalLayout from '@/layouts/VerticalLayoutAudit.vue';
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

// ─── TYPES ────────────────────────────────────────────────────────────────────
interface Auditor {
    id: number; audit_code: string; first_name: string; last_name: string;
    nom_complet: string; initiales: string; avatar?: string; entity?: string;
    audit_experience?: number; status: string;
}

interface Affectation {
    id: number;
    mission_id: number;
    entites?: string;
    code_mission: string; libelle: string; objectif?: string; description?: string;
    type_mission?: string; lieux?: string; entities_list?: string;
    date_debut: string; date_fin: string;
    date_debut_fr: string; date_fin_fr: string;
    duree: number; status: string; progression: number;
    mon_role: string; role_libelle: string; budget_individuel: number;
    processus?: { nom: string; code: string; description?: string } | null;
    processus_nom?: string; processus_code?: string; processus_description?: string;
    risques: Risque[];
    nb_risques: number;
}

interface AffectationEntity {
    id: number; mission_id: number;
    entity_id: number | null; entity_name: string | null; entity_code?: string | null;
    entity_date_debut: string; entity_date_fin: string;
    entity_date_debut_fr: string; entity_date_fin_fr: string;
    date_debut: string; date_fin: string;
    date_debut_fr: string; date_fin_fr: string;
    code_mission: string; libelle: string; objectif?: string; description?: string;
    type_mission?: string; lieux?: string;
    status: string; mon_role: string; role_libelle: string;
    duree: number; progression: number; budget_individuel: number;
    processus_nom?: string | null; processus_code?: string | null; processus_description?: string | null;
    risques: Risque[];
    nb_risques: number;
}

interface Risque {
    id: number; titre: string; description?: string | null;
    probabilite?: string | null; impact?: string | null;
    niveau?: string | null; mesures?: string | null; statut?: string;
}

interface MembreMission {
    auditeur_id: number; audit_code: string; first_name: string; last_name: string;
    avatar?: string; role: string; role_libelle: string; is_me: boolean;
}
interface EquipeMission { total: number; membres: MembreMission[]; }
interface BudgetLigne   { libelle: string; montant: number; entity_name: string; }
interface Stats {
    mes_missions: number; en_cours: number; planifiees: number; terminees: number;
    annulees: number; jours_total: number; budget_total: number; taux_realisation: number;
    nb_risques_total: number;
}
interface CalendrierMois {
    mois: number; label: string; label_long: string;
    status: string; status_label: string;
    nb_missions: number; jours: number; semaines: number;
    missions: {
        id: number; mission_id: number; code_mission: string; libelle: string;
        status: string; mon_role: string; processus_nom?: string;
        entity_id: number | null; entity_name: string | null;
        date_debut: string; date_fin: string; date_debut_fr: string; date_fin_fr: string;
        jours_dans_mois: number; semaines_dans_mois: number; nb_risques: number;
    }[];
}

// ─── PROPS ────────────────────────────────────────────────────────────────────
const props = defineProps<{
    auditor: Auditor;
    affectations: Affectation[];
    affectationEntities: AffectationEntity[];
    equipesParMission: Record<number, EquipeMission>;
    budgetLignes: Record<number, BudgetLigne[]>;
    stats: Stats;
    prochaineMission?: Affectation;
    missionEnCours?: Affectation;
    calendrier: CalendrierMois[];
    currentYear: number;
}>();

// ─── STATE ────────────────────────────────────────────────────────────────────
const activeTab       = ref<'overview' | 'missions' | 'calendrier' | 'budget'>('overview');
const filterStatus    = ref('all');
const searchMission   = ref('');
const selectedMission = ref<AffectationEntity | Affectation | null>(null);
const detailTab       = ref<'infos' | 'equipe' | 'risques' | 'guide'>('infos');
const toast           = ref({ show: false, message: '', type: 'success' as 'success' | 'error' });

// ─── TABS CONFIG ──────────────────────────────────────────────────────────────
const tabs = computed(() => [
    { id: 'overview',   label: "Vue d'ensemble", icon: 'dashboard',      badge: null },
    { id: 'missions',   label: 'Mes Missions',   icon: 'briefcase',      badge: missionsDemarrables.value.length || null },
    { id: 'calendrier', label: 'Planning Gantt', icon: 'calendar-stats', badge: null },
    { id: 'budget',     label: 'Budget',         icon: 'coin',           badge: null },
]);

const detailTabs = [
    { id: 'infos',  label: 'Infos',   icon: 'info-circle' },
    { id: 'equipe', label: 'Équipe',  icon: 'users' },
    { id: 'risques',label: 'Risques', icon: 'alert-triangle' },
    { id: 'guide',  label: 'Guide',   icon: 'book' },
];

const statusOptions = [
    { value: 'all',       text: 'Tous les statuts' },
    { value: 'planifiee', text: 'Planifiée' },
    { value: 'en_cours',  text: 'En cours' },
    { value: 'terminee',  text: 'Terminée' },
    { value: 'annulee',   text: 'Annulée' },
];

// ─── COMPUTED ─────────────────────────────────────────────────────────────────
const missionsDemarrables = computed(() => props.affectations.filter(a => canStart(a)));

const filteredMissions = computed(() => {
    let list = [...props.affectations];
    if (filterStatus.value !== 'all') list = list.filter(a => a.status === filterStatus.value);
    if (searchMission.value) {
        const q = searchMission.value.toLowerCase();
        list = list.filter(a =>
            a.code_mission.toLowerCase().includes(q) ||
            a.libelle.toLowerCase().includes(q) ||
            (a.entities_list ?? '').toLowerCase().includes(q)
        );
    }
    return list.sort((a, b) => (canStart(a) ? 0 : 1) - (canStart(b) ? 0 : 1));
});

const kpiCards = computed(() => {
    const s = props.stats;
    const total = s.mes_missions || 1;
    return [
        { label: 'Total Missions', value: s.mes_missions, icon: 'briefcase',      color: 'primary', trendValue: s.mes_missions + '',                          trendColor: 'primary',  progress: 100 },
        { label: 'En Cours',       value: s.en_cours,     icon: 'player-play',    color: 'warning', trendValue: s.en_cours > 0 ? 'Actif' : 'Aucune',          trendColor: s.en_cours > 0 ? 'warning' : 'secondary', progress: Math.round(s.en_cours / total * 100) },
        { label: 'Terminées',      value: s.terminees,    icon: 'check-circle',   color: 'success', trendValue: s.taux_realisation + '%',                      trendColor: 'success',  progress: s.taux_realisation },
        { label: 'Jours Audit',    value: s.jours_total,  icon: 'clock',          color: 'info',    trendValue: s.jours_total + 'j',                           trendColor: 'info',     progress: 75 },
        { label: 'Planifiées',     value: s.planifiees,   icon: 'calendar-event', color: 'warning', trendValue: s.planifiees + ' rest.',                       trendColor: 'warning',  progress: Math.round(s.planifiees / total * 100) },
        { label: 'Risques',        value: s.nb_risques_total, icon: 'alert-triangle', color: 'danger', trendValue: s.nb_risques_total === 0 ? 'Aucun' : s.nb_risques_total + '', trendColor: s.nb_risques_total > 0 ? 'danger' : 'success', progress: Math.min(s.nb_risques_total * 10, 100) },
    ];
});

const affectationsAvecBudget = computed(() => props.affectations.filter(a => (a.budget_individuel ?? 0) > 0).length);
const budgetMoyen = computed(() => affectationsAvecBudget.value ? props.stats.budget_total / affectationsAvecBudget.value : 0);

const selectedMissionEntities = computed((): AffectationEntity[] => {
    if (!selectedMission.value) return [];
    const sm = selectedMission.value as AffectationEntity;
    return props.affectationEntities.filter(ae => ae.id === sm.id && ae.entity_id !== null);
});

const selectedMissionRisques = computed((): Risque[] => {
    if (!selectedMission.value) return [];
    const sm = selectedMission.value as AffectationEntity;
    return sm.risques ?? [];
});

const risquesEleves = computed(() =>
    selectedMissionRisques.value.filter(r =>
        r.niveau && ['critique', 'Critique', 'eleve', 'Élevé', 'eleve', 'haut', 'Haut', 'Considérable'].includes(r.niveau)
    )
);

/** Groupes Gantt : une entrée par mission (mission_id unique) avec ses entités */
const ganttGroupes = computed(() => {
    const map = new Map<number, {
        id: number; mission_id: number; code_mission: string; libelle: string;
        objectif?: string; lieux?: string; status: string; progression: number;
        date_debut: string; date_fin: string; date_debut_fr: string; date_fin_fr: string;
        processus_nom?: string | null; nb_risques: number;
        entities: AffectationEntity[];
    }>();

    for (const ae of props.affectationEntities) {
        if (!map.has(ae.mission_id)) {
            map.set(ae.mission_id, {
                id: ae.id, mission_id: ae.mission_id,
                code_mission: ae.code_mission, libelle: ae.libelle,
                objectif: ae.objectif, lieux: ae.lieux,
                status: ae.status, progression: ae.progression,
                date_debut: ae.date_debut, date_fin: ae.date_fin,
                date_debut_fr: ae.date_debut_fr, date_fin_fr: ae.date_fin_fr,
                processus_nom: ae.processus_nom,
                nb_risques: ae.nb_risques,
                entities: [],
            });
        }
        map.get(ae.mission_id)!.entities.push(ae);
    }

    return Array.from(map.values());
});

const checklistDemarrage = computed(() => {
    const sm = selectedMission.value as AffectationEntity | null;
    if (!sm) return [];
    const isStarted = sm.status !== 'planifiee';
    return [
        { label: 'Lettre de mission reçue',        desc: 'Confirmation officielle de la mission',    done: isStarted },
        { label: 'Interlocuteurs identifiés',       desc: 'Contacts au sein des entités auditées',    done: isStarted },
        { label: 'Programme de travail établi',     desc: 'Plan détaillé des activités à réaliser',   done: isStarted },
        { label: 'Documents préliminaires collectés',desc: 'Rapports antérieurs, procédures, organigrammes', done: sm.status === 'terminee' || sm.status === 'en_cours' },
        { label: 'Réunion d ouverture tenue',       desc: 'Présentation de l\'équipe et des objectifs', done: sm.status === 'terminee' },
    ];
});

const phasesMission = computed(() => {
    const sm = selectedMission.value as AffectationEntity | null;
    if (!sm) return [];
    const phases = ['Planification', 'Collecte documents', 'Analyse & Tests', 'Rédaction rapport', 'Validation', 'Clôture'];
    const phaseIdx = sm.status === 'planifiee' ? 0 : sm.status === 'en_cours' ? 2 : sm.status === 'terminee' ? 5 : -1;
    return phases.map((label, i) => ({
        label,
        done:    i < phaseIdx,
        current: i === phaseIdx,
    }));
});

// ─── LÉGENDES ─────────────────────────────────────────────────────────────────
const legendeCalendrier = [
    { label: 'Libre',     color: '#F0FDF4' },
    { label: 'En cours',  color: '#BFDBFE' },
    { label: 'Planifiée', color: '#FEF3C7' },
    { label: 'Terminée',  color: '#DCFCE7' },
    { label: 'Annulée',   color: '#FEE2E2' },
];
const legendeStatuts = [
    { status: 'planifiee', label: 'Planifiée', color: '#D97706' },
    { status: 'en_cours',  label: 'En cours',  color: '#1E40AF' },
    { status: 'terminee',  label: 'Terminée',  color: '#059669' },
    { status: 'annulee',   label: 'Annulée',   color: '#DC2626' },
];

// ─── CONSTANTES ───────────────────────────────────────────────────────────────
const MONTH_HEADER_COLORS: Record<number, string> = {
    1:'#1D4ED8',2:'#0369A1',3:'#0F766E',4:'#15803D',5:'#65A30D',6:'#CA8A04',
    7:'#C2410C',8:'#B91C1C',9:'#9333EA',10:'#1D4ED8',11:'#0369A1',12:'#0F766E',
};
const STATUS_COLORS: Record<string, string> = {
    planifiee: '#D97706', en_cours: '#1E40AF', terminee: '#059669', annulee: '#DC2626',
};
const STATUS_BG_COLORS: Record<string, string> = {
    en_cours: '#DBEAFE', planifiee: '#FEF3C7', terminee: '#D1FAE5', annulee: '#FEE2E2',
};
const STATUS_LABELS: Record<string, string> = {
    planifiee: 'Planifiée', en_cours: 'En cours', terminee: 'Terminée', annulee: 'Annulée',
};
const STATUS_BADGE_CLASSES: Record<string, string> = {
    en_cours:  'bg-primary-subtle text-primary',
    terminee:  'bg-success-subtle text-success',
    annulee:   'bg-danger-subtle text-danger',
    planifiee: 'bg-warning-subtle text-warning',
};
const ROLE_BADGE_CLASSES: Record<string, string> = {
    DM: 'bg-primary-subtle text-primary',
    CM: 'bg-info-subtle text-info',
    AS: 'bg-warning-subtle text-warning',
    AJ: 'bg-secondary-subtle text-secondary',
};
const PROGRESS_BAR_CLASSES: Record<string, string> = {
    en_cours: 'bg-primary', terminee: 'bg-success', planifiee: 'bg-warning', annulee: 'bg-danger',
};

// ─── HELPERS ──────────────────────────────────────────────────────────────────
function ganttColor(status: string): string {
    return STATUS_COLORS[status] ?? '#94A3B8';
}
function moisBgColorByStatus(status: string): string {
    return STATUS_BG_COLORS[status] ?? '#F0FDF4';
}
function statusLabel(s: string): string {
    return STATUS_LABELS[s] ?? s;
}
function statusBadgeClass(status: string): string {
    return STATUS_BADGE_CLASSES[status] ?? 'bg-secondary-subtle text-secondary';
}
function roleBadgeClass(role?: string): string {
    return ROLE_BADGE_CLASSES[role ?? ''] ?? 'bg-light text-muted';
}
function progressBarClass(status: string): string {
    return PROGRESS_BAR_CLASSES[status] ?? 'bg-secondary';
}
function moisHeaderColor(mois: CalendrierMois): string {
    return MONTH_HEADER_COLORS[mois.mois] ?? '#6B7280';
}
function moisBgColor(mois: CalendrierMois): string {
    const map: Record<string, string> = { en_cours:'#BFDBFE', planifiee:'#FEF3C7', terminee:'#DCFCE7', annulee:'#FEE2E2', libre:'#F0FDF4' };
    return map[mois.status] ?? '#F0FDF4';
}
function moisTextColor(mois: CalendrierMois): string {
    const map: Record<string, string> = { en_cours:'#1E40AF', planifiee:'#D97706', terminee:'#059669', annulee:'#DC2626', libre:'#6B7280' };
    return map[mois.status] ?? '#6B7280';
}
function niveauColor(niveau?: string | null): string {
    if (!niveau) return '#94A3B8';
    const n = niveau.toLowerCase();
    if (n.includes('critique') || n.includes('critic'))    return '#DC2626';
    if (n.includes('lev') || n.includes('consider') || n.includes('haut')) return '#EA580C';
    if (n.includes('moyen') || n.includes('medium'))        return '#D97706';
    if (n.includes('faible') || n.includes('low'))          return '#059669';
    return '#6B7280';
}

function parseDate(str: string): Date {
    const d = new Date(str || 0);
    d.setHours(0, 0, 0, 0);
    return d;
}
function canStart(aff: Affectation | AffectationEntity): boolean {
    if (aff.status !== 'planifiee') return false;
    const today = new Date(); today.setHours(0, 0, 0, 0);
    return parseDate(aff.date_debut) <= today;
}
function countdownDays(aff: Affectation | AffectationEntity): number {
    const today = new Date(); today.setHours(0, 0, 0, 0);
    return Math.max(0, Math.ceil((parseDate(aff.date_debut).getTime() - today.getTime()) / 86400000));
}
function formatMontant(n: number): string {
    if (!n) return '0 FCFA';
    return new Intl.NumberFormat('fr-FR').format(n) + ' FCFA';
}
function formatMontantCourt(n: number): string {
    if (!n) return '0';
    if (n >= 1_000_000) return (Math.round(n / 10_000) / 100).toFixed(2).replace('.', ',') + 'M';
    if (n >= 1_000)     return Math.round(n / 1_000) + 'K';
    return n + '';
}
function truncate(str: string | undefined | null, n: number): string {
    if (!str) return '—';
    return str.length > n ? str.slice(0, n) + '…' : str;
}
function affEntitiesForMission(aff: Affectation): AffectationEntity[] {
    return props.affectationEntities.filter(ae => ae.id === aff.id && ae.entity_id !== null);
}
function inRangeEntity(ae: AffectationEntity, m: number): boolean {
    const dateDebut = ae.entity_date_debut || ae.date_debut;
    const dateFin   = ae.entity_date_fin   || ae.date_fin;
    if (!dateDebut || !dateFin) return false;
    const deb = parseInt(dateDebut.split('-')[1], 10);
    const fin = parseInt(dateFin.split('-')[1],   10);
    return deb <= m && m <= fin;
}
function isStartMonthEntity(ae: AffectationEntity, m: number): boolean {
    const dateDebut = ae.entity_date_debut || ae.date_debut;
    if (!dateDebut) return false;
    return parseInt(dateDebut.split('-')[1], 10) === m;
}
function isEndMonthEntity(ae: AffectationEntity, m: number): boolean {
    const dateFin = ae.entity_date_fin || ae.date_fin;
    if (!dateFin) return false;
    return parseInt(dateFin.split('-')[1], 10) === m;
}
function inRangeGlobal(group: { date_debut: string; date_fin: string }, m: number): boolean {
    if (!group.date_debut || !group.date_fin) return false;
    const deb = parseInt(group.date_debut.split('-')[1], 10);
    const fin = parseInt(group.date_fin.split('-')[1],   10);
    return deb <= m && m <= fin;
}
function entityDuration(ae: AffectationEntity): number {
    const d = ae.entity_date_debut || ae.date_debut;
    const f = ae.entity_date_fin   || ae.date_fin;
    if (!d || !f) return 0;
    return Math.max(1, Math.ceil((new Date(f).getTime() - new Date(d).getTime()) / 86400000) + 1);
}

// ─── ACTIONS ──────────────────────────────────────────────────────────────────
function openDetail(aff: Affectation | AffectationEntity) {
    selectedMission.value = aff as AffectationEntity;
    detailTab.value = 'infos';
}
function openDetailById(affId: number) {
    const ae = props.affectationEntities.find(a => a.id === affId);
    if (ae) openDetail(ae);
}
function startMission(aff: Affectation | AffectationEntity) {
    router.patch(route('audit.core.programmation-missions.update-status', aff.mission_id), { status: 'en_cours' }, {
        onSuccess: () => { showToast('Mission démarrée avec succès !', 'success'); selectedMission.value = null; },
        onError:   () =>   showToast('Erreur lors du démarrage de la mission.', 'error'),
    });
}
function showToast(message: string, type: 'success' | 'error') {
    toast.value = { show: true, message, type };
    setTimeout(() => { toast.value.show = false; }, 4000);
}
</script>