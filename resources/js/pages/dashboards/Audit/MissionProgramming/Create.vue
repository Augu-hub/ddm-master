<template>
    <VerticalLayout>
        <Head title="Programmation de mission" />

        <!-- ░░ HEADER ░░ -->
        <div class="page-header mb-4">
            <div class="d-flex align-items-center gap-3">
                <div class="ph-icon"><i class="ti ti-calendar-plus"></i></div>
                <div>
                    <h3 class="ph-title mb-0">Programmation de mission</h3>
                    <p class="ph-sub mb-0">Planifiez et affectez les auditeurs à une mission</p>
                </div>
                <div class="ms-auto d-flex align-items-center gap-2">
                    <span class="badge-code">{{ form.code_programmation || '—' }}</span>
                    <span class="badge-phase">PROG</span>
                </div>
            </div>
        </div>

        <!-- ░░ FLASH / ERRORS ░░ -->
        <transition name="slide-down">
            <div v-if="$page.props.flash?.success" class="alert-pro alert-pro-success mb-3">
                <i class="ti ti-circle-check"></i><span>{{ $page.props.flash.success }}</span>
            </div>
        </transition>
        <transition name="slide-down">
            <div v-if="form.errors && Object.keys(form.errors).length" class="alert-pro alert-pro-danger mb-3">
                <i class="ti ti-alert-triangle"></i>
                <div><p v-for="(e, k) in form.errors" :key="k" class="mb-0">{{ e }}</p></div>
            </div>
        </transition>
        <transition name="slide-down">
            <div v-if="selectionWarning" class="alert-pro alert-pro-warning mb-3">
                <i class="ti ti-alert-triangle"></i>
                <span>{{ selectionWarning }}</span>
                <button @click="selectionWarning = null"><i class="ti ti-x"></i></button>
            </div>
        </transition>

        <b-row class="g-3">
            <!-- ╔════════ COLONNE GAUCHE ════════╗ -->
            <b-col lg="6">

                <!-- CARD : Programmation -->
                <div class="pro-card mb-3">
                    <div class="pro-card-header">
                        <i class="ti ti-settings-2 me-2 text-primary"></i>
                        <span>Programmation de la mission</span>
                    </div>
                    <div class="pro-card-body">
                        <div class="form-row">
                            <label class="form-lbl" style="min-width:88px;">Mission</label>
                            <div class="mission-picker flex-grow-1" :class="form.mission_id ? 'picked' : ''" @click="showMissionModal = true">
                                <i class="ti ti-search picker-icon"></i>
                                <span v-if="!form.mission_id" class="picker-placeholder">Cliquer pour sélectionner une mission…</span>
                                <span v-else class="picker-value">{{ form.code_mission }}</span>
                                <i class="ti ti-chevron-down picker-chevron"></i>
                            </div>
                            <button v-if="form.mission_id" class="btn-icon-danger" title="Effacer" @click="clearMission">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>
                        <div class="form-row">
                            <label class="form-lbl" style="min-width:88px;">Code prog.</label>
                            <input v-model="form.code_programmation" class="pro-input font-mono fw-bold text-primary" style="width:130px;" />
                            <label class="form-lbl ms-2">N° FPM</label>
                            <input v-model="form.fpm_number" class="pro-input bg-light" readonly style="width:120px;" />
                        </div>
                        <div class="form-row">
                            <label class="form-lbl" style="min-width:88px;">Libellé</label>
                            <input v-model="form.libelle" class="pro-input flex-grow-1 bg-light" readonly />
                        </div>
                        <div class="form-row mb-0">
                            <label class="form-lbl" style="min-width:88px;">Lieu</label>
                            <input v-model="form.lieux" class="pro-input flex-grow-1" placeholder="Lieu d'exécution…" />
                        </div>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════════════
                     ENTITÉS LIÉES — chargées depuis audit_mission_entities
                     Chaque entité possède sa propre période modifiable
                     ═══════════════════════════════════════════════════════ -->
                <div class="pro-card mb-3">
                    <div class="pro-card-header">
                        <i class="ti ti-building me-2 text-primary"></i>
                        <span>Entités liées à la mission</span>
                        <span class="ms-2 count-badge">{{ entityPeriods.length }}</span>
                        <span v-if="loadingEntities" class="ms-2 d-flex align-items-center gap-1 text-muted" style="font-size:.68rem;">
                            <span class="spinner-border spinner-border-sm" style="width:10px;height:10px;"></span> Chargement…
                        </span>
                        <!-- Résumé période globale dans l'en-tête -->
                        <div v-if="globalPeriod.start && globalPeriod.end" class="ms-auto">
                            <span class="period-summary-badge">
                                <i class="ti ti-calendar me-1"></i>
                                {{ fmtDate(globalPeriod.start) }} → {{ fmtDate(globalPeriod.end) }}
                                <span class="ms-1" style="opacity:.7;">({{ globalPeriod.duration }}j)</span>
                            </span>
                        </div>
                    </div>

                    <!-- Aucune mission sélectionnée -->
                    <div v-if="!form.mission_id" class="pro-card-body">
                        <div class="empty-state-entities">
                            <i class="ti ti-building-off"></i>
                            <span>Sélectionnez une mission pour charger ses entités liées</span>
                        </div>
                    </div>

                    <!-- Chargement -->
                    <div v-else-if="loadingEntities" class="pro-card-body">
                        <div class="empty-state-entities">
                            <span class="spinner-border spinner-border-sm me-2"></span>
                            <span>Chargement des entités depuis la base…</span>
                        </div>
                    </div>

                    <!-- Aucune entité -->
                    <div v-else-if="!entityPeriods.length" class="pro-card-body">
                        <div class="empty-state-entities" style="color:#d97706;">
                            <i class="ti ti-alert-triangle"></i>
                            <span>Aucune entité liée à cette mission dans <code>audit_mission_entities</code></span>
                        </div>
                    </div>

                    <!-- ✅ Tableau entités × dates -->
                    <div v-else class="pro-card-body p-0">
                        <!-- En-tête colonnes -->
                        <div class="ept-header">
                            <div class="ept-col ept-code">Code</div>
                            <div class="ept-col ept-name">Entité</div>
                            <div class="ept-col ept-date">Date début <span style="color:#f87171;">*</span></div>
                            <div class="ept-col ept-date">Date fin <span style="color:#f87171;">*</span></div>
                            <div class="ept-col ept-dur">Durée</div>
                            <div class="ept-col ept-dispo">Auditeurs affectés</div>
                        </div>

                        <!-- Lignes -->
                        <div v-for="(ep, idx) in entityPeriods" :key="ep.entity_id"
                            class="ept-row"
                            :class="{
                                'ept-row-alt':     idx % 2 === 1,
                                'ept-row-invalid': ep.hasDateError,
                                'ept-row-overlap': entityHasOverlap(ep),
                            }">
                            <!-- Code -->
                            <div class="ept-col ept-code">
                                <span class="entity-code-badge" :style="{ background: entityColor(idx) + '20', color: entityColor(idx), borderColor: entityColor(idx) + '40' }">
                                    {{ ep.entity_code || '—' }}
                                </span>
                            </div>

                            <!-- Nom -->
                            <div class="ept-col ept-name">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="entity-dot" :style="{ background: entityColor(idx) }"></span>
                                    <span class="entity-name-text">{{ ep.entity_name }}</span>
                                </div>
                            </div>

                            <!-- Date début -->
                            <div class="ept-col ept-date">
                                <input v-model="ep.planned_start_date" type="date" class="pro-input w-100"
                                    :class="{ 'input-error': ep.hasDateError }"
                                    @change="onEntityDateChange(ep)" />
                            </div>

                            <!-- Date fin -->
                            <div class="ept-col ept-date">
                                <input v-model="ep.planned_end_date" type="date" class="pro-input w-100"
                                    :class="{ 'input-error': ep.hasDateError }"
                                    :min="ep.planned_start_date"
                                    @change="onEntityDateChange(ep)" />
                            </div>

                            <!-- Durée -->
                            <div class="ept-col ept-dur">
                                <div class="duration-mini" :class="{ 'duration-warn': ep.hasDateError }">
                                    <span v-if="ep.planned_start_date && ep.planned_end_date && !ep.hasDateError">
                                        {{ calcDuration(ep.planned_start_date, ep.planned_end_date) }}j
                                    </span>
                                    <span v-else class="text-muted">—</span>
                                </div>
                            </div>

                            <!-- Dispo auditeurs pour cette entité -->
                            <div class="ept-col ept-dispo">
                                <template v-if="ep.planned_start_date && ep.planned_end_date">
                                    <div class="dispo-pills">
                                        <span v-for="aud in auditeursSelectionnes.filter(a => a.affectations[ep.entity_id])"
                                            :key="aud.auditeur_id"
                                            class="dispo-pill"
                                            :class="aud.dispoParEntite[ep.entity_id] ? 'dispo-ok' : 'dispo-ko'"
                                            :title="aud.audit_code + ' : ' + (aud.dispoParEntite[ep.entity_id] ? 'Disponible' : 'Indisponible')">
                                            {{ aud.audit_code }}
                                        </span>
                                        <span v-if="!auditeursSelectionnes.filter(a => a.affectations[ep.entity_id]).length"
                                            style="font-size:.62rem;color:#94a3b8;">Aucun</span>
                                    </div>
                                </template>
                                <span v-else style="font-size:.62rem;color:#94a3b8;">—</span>
                            </div>
                        </div>

                        <!-- Barres d'alerte -->
                        <div v-if="entityPeriods.some(ep => ep.hasDateError)" class="ept-error-bar">
                            <i class="ti ti-alert-triangle me-1"></i>
                            Certaines entités ont des dates invalides (fin avant début).
                        </div>
                        <div v-if="hasEntityOverlap" class="ept-overlap-bar">
                            <i class="ti ti-alert-triangle me-1"></i>
                            Des entités ont des périodes qui se chevauchent.
                        </div>

                        <!-- Résumé global + bouton reset -->
                        <div class="ept-global-summary">
                            <div class="d-flex align-items-center gap-3 flex-wrap">
                                <div class="global-period-item">
                                    <span class="gpi-label">Début global</span>
                                    <span class="gpi-value">{{ fmtDate(globalPeriod.start) }}</span>
                                </div>
                                <div class="global-period-item">
                                    <span class="gpi-label">Fin globale</span>
                                    <span class="gpi-value">{{ fmtDate(globalPeriod.end) }}</span>
                                </div>
                                <div class="global-period-item">
                                    <span class="gpi-label">Durée totale</span>
                                    <span class="gpi-value fw-bold">{{ globalPeriod.duration }} jours</span>
                                </div>
                                <div class="ms-auto">
                                    <button class="pro-btn pro-btn-sm pro-btn-ghost" @click="resetAllDates"
                                        title="Remettre les dates originales de la mission pour toutes les entités">
                                        <i class="ti ti-refresh me-1"></i>Réinitialiser dates
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CARD : Équipe sélectionnée -->
                <div class="pro-card mb-3">
                    <div class="pro-card-header">
                        <i class="ti ti-users me-2 text-primary"></i>
                        <span>Équipe d'audit sélectionnée</span>
                        <span class="ms-auto count-badge">{{ auditeursSelectionnes.length }}</span>
                    </div>
                    <div class="pro-card-body p-0">
                        <div class="scroll-zone scroll-zone-sel">
                            <table class="sel-table">
                                <thead class="sticky-thead">
                                    <tr>
                                        <th style="width:64px;">Code</th>
                                        <th>Nom & Prénom</th>
                                        <th style="width:48px;">Rôle</th>
                                        <th style="width:72px;">Budget</th>
                                        <th style="width:52px;">Sup.</th>
                                        <th style="width:44px;">Dispo</th>
                                        <!-- Colonnes entités dynamiques -->
                                        <th v-for="ep in entityPeriods" :key="'th-'+ep.entity_id"
                                            style="width:70px;"
                                            :title="ep.entity_name + ' — ' + fmtDate(ep.planned_start_date) + ' → ' + fmtDate(ep.planned_end_date)">
                                            <div style="display:flex;flex-direction:column;align-items:center;gap:1px;">
                                                <span style="font-size:.55rem;font-weight:800;">{{ ep.entity_code || truncate(ep.entity_name,6) }}</span>
                                                <span style="font-size:.5rem;opacity:.65;">{{ ep.planned_start_date ? fmtDate(ep.planned_start_date).slice(0,5) : '—' }}</span>
                                            </div>
                                        </th>
                                        <th style="width:24px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(aud, idx) in auditeursSelectionnes" :key="'sel-'+aud.auditeur_id"
                                        :class="{ 'row-active': idx === activeSelIdx, 'row-warn': !aud.isAvailable }"
                                        @click="activeSelIdx = idx">
                                        <td><span class="code-chip">{{ aud.audit_code }}</span></td>
                                        <td>
                                            <div class="name-cell">
                                                <span class="name-avatar" :class="!aud.isAvailable ? 'avatar-warn':''">{{ (aud.last_name||'?').charAt(0) }}</span>
                                                <div class="name-text">
                                                    <div class="fw-semibold" style="font-size:.7rem;">{{ aud.last_name }}</div>
                                                    <div class="sub-text">{{ aud.first_name }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="role-chip" :class="'role-'+aud.role">{{ aud.role || '—' }}</span></td>
                                        <td><input v-model.number="aud.budget" type="number" min="0" class="pro-input text-end" style="width:64px;" readonly @click.stop /></td>
                                        <td><span class="sup-text">{{ aud.parent_code || '—' }}</span></td>
                                        <td>
                                            <span v-if="!aud.isAvailable" class="warn-icon warn-icon-amber" title="Indisponible"><i class="ti ti-calendar-x"></i></span>
                                            <span v-else class="warn-icon warn-icon-green" title="Disponible"><i class="ti ti-calendar-check"></i></span>
                                        </td>
                                        <!-- Cases à cocher par entité avec indicateur dispo -->
                                        <td v-for="ep in entityPeriods" :key="'aff-'+aud.auditeur_id+'-'+ep.entity_id"
                                            class="text-center" @click.stop>
                                            <div class="entity-checkbox-wrapper"
                                                :class="{ 'indisponible': !aud.dispoParEntite[ep.entity_id] }"
                                                :title="tooltipContent(aud, ep.entity_id)">
                                                <input type="checkbox" v-model="aud.affectations[ep.entity_id]"
                                                    class="entity-checkbox"
                                                    @change="updateAuditeurDispo(aud)" />
                                            </div>
                                        </td>
                                        <td>
                                            <button class="btn-icon-danger" @click.stop="deselectAuditeur(aud)"><i class="ti ti-x"></i></button>
                                        </td>
                                    </tr>
                                    <tr v-if="!auditeursSelectionnes.length">
                                        <td :colspan="6 + entityPeriods.length + 1" class="empty-row">
                                            <i class="ti ti-users-minus"></i><span>Aucun auditeur sélectionné</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- CARD : Budget fixe -->
                <div class="pro-card mb-3">
                    <div class="pro-card-header">
                        <i class="ti ti-coin me-2 text-primary"></i>
                        <span>Budget fixe</span>
                    </div>
                    <div class="pro-card-body">
                        <div class="form-row mb-0">
                            <label class="form-lbl" style="min-width:88px;">Montant fixe</label>
                            <div class="currency-input" style="max-width:220px;">
                                <input v-model.number="form.montant_fixe" type="number" min="0" class="pro-input text-end" />
                                <span class="currency-unit">FCFA</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CARD : Budget variable global -->
                <div class="pro-card mb-3">
                    <div class="pro-card-header">
                        <i class="ti ti-receipt me-2 text-primary"></i>
                        <span>Budget variable (global mission)</span>
                    </div>
                    <div class="pro-card-body p-0">
                        <div class="scroll-zone scroll-zone-budget">
                            <table class="budget-table">
                                <thead class="sticky-thead">
                                    <tr>
                                        <th>Catégorie / Libellé</th>
                                        <th class="text-end" style="width:160px;">Montant (FCFA)</th>
                                        <th style="width:30px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(line, idx) in budgetLines" :key="'bl-'+idx">
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="cat-dot" :style="{ background: line.isCustom ? '#10b981':'#8b5cf6' }"></span>
                                                <template v-if="line.isCustom">
                                                    <input v-model="line.custom_label" class="pro-input" placeholder="Libellé" style="width:140px;" />
                                                </template>
                                                <template v-else>
                                                    <span style="font-size:.75rem;">{{ line.libelle }}</span>
                                                </template>
                                            </div>
                                        </td>
                                        <td>
                                            <input v-model.number="line.montant" type="number" min="0" class="pro-input text-end" style="width:100%;" />
                                        </td>
                                        <td>
                                            <button v-if="line.isCustom" class="btn-icon-danger" @click="removeBudgetLine(idx)"><i class="ti ti-trash"></i></button>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="total-row">
                                        <td class="fw-semibold" style="font-size:.75rem;">Total variable</td>
                                        <td class="text-end fw-bold text-primary" style="font-size:.8rem;">{{ fmt(totalBudgetVariable) }} FCFA</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div class="d-flex justify-content-end p-2 border-top">
                            <button class="pro-btn pro-btn-sm pro-btn-outline" @click="addCustomBudgetLine">
                                <i class="ti ti-plus me-1"></i>Ajouter ligne personnalisée
                            </button>
                        </div>
                    </div>
                </div>

                <!-- CARD : Budget par auditeur (matriciel entités × catégories) -->
                <div v-if="auditeursSelectionnes.length > 0 && entityPeriods.length > 0" class="pro-card">
                    <div class="pro-card-header">
                        <i class="ti ti-users-group me-2 text-primary"></i>
                        <span>Budget variable par auditeur</span>
                        <span class="ms-auto count-badge">{{ auditeursSelectionnes.length }}</span>
                    </div>
                    <div class="pro-card-body p-0">
                        <div class="scroll-zone-tabs">
                            <div class="aud-tabs">
                                <button v-for="(aud, idx) in auditeursSelectionnes" :key="'tab-'+aud.auditeur_id"
                                    class="aud-tab" :class="{ 'aud-tab-active': activeBudgetAuditeur === idx }"
                                    @click="activeBudgetAuditeur = idx">
                                    <span class="tab-avatar" :class="'color-'+colorIdx(aud.audit_code)">{{ (aud.last_name||'?').charAt(0) }}</span>
                                    <span class="tab-name">{{ aud.audit_code }}</span>
                                    <span v-if="getTotalBudgetAuditeur(aud) > 0" class="tab-amount">{{ fmtShort(getTotalBudgetAuditeur(aud)) }}</span>
                                </button>
                            </div>
                        </div>
                        <div v-if="auditeursSelectionnes[activeBudgetAuditeur]" class="aud-budget-panel">
                            <div class="aud-budget-header">
                                <span class="name-avatar" :class="'color-'+colorIdx(auditeursSelectionnes[activeBudgetAuditeur].audit_code)">
                                    {{ (auditeursSelectionnes[activeBudgetAuditeur].last_name||'?').charAt(0) }}
                                </span>
                                <div>
                                    <div class="fw-semibold" style="font-size:.75rem;">
                                        {{ auditeursSelectionnes[activeBudgetAuditeur].last_name }} {{ auditeursSelectionnes[activeBudgetAuditeur].first_name }}
                                    </div>
                                    <div style="font-size:.68rem;color:#94a3b8;">{{ auditeursSelectionnes[activeBudgetAuditeur].audit_code }}</div>
                                </div>
                                <div class="ms-auto text-end">
                                    <div style="font-size:.65rem;color:#94a3b8;">Total perso</div>
                                    <div class="fw-bold text-primary" style="font-size:.82rem;">{{ fmt(getTotalBudgetAuditeur(auditeursSelectionnes[activeBudgetAuditeur])) }} FCFA</div>
                                </div>
                            </div>
                            <div class="scroll-zone scroll-zone-aud-budget">
                                <table class="budget-table">
                                    <thead class="sticky-thead">
                                        <tr>
                                            <th>Catégorie / Libellé</th>
                                            <th v-for="ep in entityPeriods" :key="'col-'+ep.entity_id" class="text-end" style="min-width:100px;">
                                                <div style="display:flex;flex-direction:column;align-items:flex-end;gap:1px;">
                                                    <span>{{ ep.entity_code || truncate(ep.entity_name, 10) }}</span>
                                                    <span style="font-size:.55rem;opacity:.65;font-weight:400;">{{ ep.planned_start_date ? calcDuration(ep.planned_start_date, ep.planned_end_date)+'j' : '' }}</span>
                                                </div>
                                            </th>
                                            <th style="width:30px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(line, idx) in auditeursSelectionnes[activeBudgetAuditeur].budgetLines" :key="'abl-'+idx">
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="cat-dot" :style="{ background: line.isCustom ? '#10b981':'#8b5cf6' }"></span>
                                                    <template v-if="line.isCustom">
                                                        <input v-model="line.custom_label" class="pro-input" placeholder="Libellé" style="width:140px;" />
                                                    </template>
                                                    <template v-else>
                                                        <span style="font-size:.75rem;">{{ line.libelle }}</span>
                                                    </template>
                                                </div>
                                            </td>
                                            <td v-for="ep in entityPeriods" :key="'cell-'+ep.entity_id" class="text-end">
                                                <input v-model.number="line.montants[ep.entity_id]" type="number" min="0"
                                                    class="pro-input text-end" style="width:100%;"
                                                    @input="updateAuditeurBudget(activeBudgetAuditeur)" />
                                            </td>
                                            <td>
                                                <button v-if="line.isCustom" class="btn-icon-danger" @click="removeAuditeurBudgetLine(activeBudgetAuditeur, idx)"><i class="ti ti-trash"></i></button>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr class="total-row">
                                            <td class="fw-semibold" style="font-size:.75rem;">Total auditeur</td>
                                            <td v-for="ep in entityPeriods" :key="'tot-'+ep.entity_id" class="text-end fw-bold" style="font-size:.8rem;color:#10b981;">
                                                {{ fmt(getTotalBudgetAuditeurByEntity(auditeursSelectionnes[activeBudgetAuditeur], ep.entity_id)) }}
                                            </td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <div class="d-flex justify-content-end p-2 border-top">
                                <button class="pro-btn pro-btn-sm pro-btn-outline" @click="addAuditeurCustomBudgetLine(activeBudgetAuditeur)">
                                    <i class="ti ti-plus me-1"></i>Ajouter ligne
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </b-col>

            <!-- ╔════════ COLONNE DROITE ════════╗ -->
            <b-col lg="6">

                <!-- Infos mission -->
                <div v-if="form.mission_id" class="pro-card mb-3">
                    <div class="pro-card-header">
                        <i class="ti ti-info-circle me-2 text-primary"></i>
                        <span>Détails de la mission</span>
                    </div>
                    <div class="pro-card-body">
                        <div class="mission-info-grid">
                            <div class="mi-item"><span class="mi-label">Code</span><span class="code-chip-lg">{{ form.code_mission || '—' }}</span></div>
                            <div class="mi-item"><span class="mi-label">Priorité</span><span class="priority-badge" :class="'prio-'+(form.priority||'').toLowerCase()">{{ form.priority || '—' }}</span></div>
                            <div class="mi-item"><span class="mi-label">Domaine</span><span class="mi-value">{{ form.domain || '—' }}</span></div>
                            <div class="mi-item"><span class="mi-label">Statut</span><span class="status-chip">{{ form.mission_status || '—' }}</span></div>
                        </div>
                        <div class="mt-2">
                            <span class="mi-label d-block mb-1">Objectif</span>
                            <div class="objective-box scroll-zone scroll-zone-objective">{{ form.objectif || '—' }}</div>
                        </div>
                    </div>
                </div>

                <!-- Hiérarchie équipe -->
                <div v-if="auditeursSelectionnes.length" class="pro-card mb-3">
                    <div class="pro-card-header">
                        <i class="ti ti-hierarchy me-2 text-primary"></i>
                        <span>Hiérarchie de l'équipe</span>
                    </div>
                    <div class="pro-card-body p-2">
                        <div class="scroll-zone scroll-zone-hierarchy">
                            <div class="hierarchy-tree">
                                <template v-for="dm in getByRole('DM')" :key="'h-dm-'+dm.auditeur_id">
                                    <div class="h-node h-node-dm">
                                        <span class="role-chip role-DM">DM</span>
                                        <span class="h-name">{{ dm.last_name }} {{ dm.first_name }}</span>
                                        <span class="code-chip" style="font-size:.6rem;">{{ dm.audit_code }}</span>
                                    </div>
                                    <template v-for="cm in getByRole('CM')" :key="'h-cm-'+cm.auditeur_id">
                                        <div class="h-node h-node-cm">
                                            <span class="h-line">└─</span>
                                            <span class="role-chip role-CM">CM</span>
                                            <span class="h-name">{{ cm.last_name }} {{ cm.first_name }}</span>
                                            <span class="code-chip" style="font-size:.6rem;">{{ cm.audit_code }}</span>
                                        </div>
                                        <template v-for="as_ in getChildrenOf(cm.auditeur_id, 'AS')" :key="'h-as-'+as_.auditeur_id">
                                            <div class="h-node h-node-as">
                                                <span class="h-line">  └─</span>
                                                <span class="role-chip role-AS">AS</span>
                                                <span class="h-name">{{ as_.last_name }} {{ as_.first_name }}</span>
                                                <span class="code-chip" style="font-size:.6rem;">{{ as_.audit_code }}</span>
                                            </div>
                                            <template v-for="aj in getChildrenOf(as_.auditeur_id, 'AJ')" :key="'h-aj-'+aj.auditeur_id">
                                                <div class="h-node h-node-aj">
                                                    <span class="h-line">    └─</span>
                                                    <span class="role-chip role-AJ">AJ</span>
                                                    <span class="h-name">{{ aj.last_name }} {{ aj.first_name }}</span>
                                                    <span class="code-chip" style="font-size:.6rem;">{{ aj.audit_code }}</span>
                                                </div>
                                            </template>
                                        </template>
                                    </template>
                                </template>
                                <template v-for="aud in getByRole('')" :key="'h-nr-'+aud.auditeur_id">
                                    <div class="h-node" style="opacity:.5;">
                                        <span class="role-chip" style="background:#f1f5f9;color:#94a3b8;">—</span>
                                        <span class="h-name">{{ aud.last_name }} {{ aud.first_name }}</span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sélection auditeurs -->
                <div class="pro-card mb-3">
                    <div class="pro-card-header">
                        <i class="ti ti-user-search me-2 text-primary"></i>
                        <span>Sélection des auditeurs</span>
                        <div class="ms-auto">
                            <div class="search-box-sm">
                                <i class="ti ti-search"></i>
                                <input v-model="audSearch" placeholder="Rechercher…" />
                            </div>
                        </div>
                    </div>
                    <div class="legend-bar">
                        <span class="leg-item"><i class="ti ti-calendar-check" style="color:#059669;"></i> Disponible</span>
                        <span class="leg-item"><i class="ti ti-calendar-x" style="color:#d97706;"></i> Indisponible</span>
                        <span class="leg-item"><i class="ti ti-certificate" style="color:#6366f1;"></i> Compétences</span>
                    </div>
                    <div class="pro-card-body p-0">
                        <div class="aud-grid-header" style="grid-template-columns:36px 1fr 62px 72px 42px 32px 24px 24px;">
                            <div></div><div>Nom / Code</div><div>Rôle</div>
                            <div>Supérieur</div><div>Comp.</div><div>Dispo</div><div></div><div>🔒</div>
                        </div>
                        <div class="scroll-zone scroll-zone-aud">
                            <template v-for="aud in filteredAuditeurs" :key="'aud-'+aud.id">
                                <div class="aud-row" style="grid-template-columns:36px 1fr 62px 72px 42px 32px 24px 24px;"
                                    :class="{ 'aud-selected': isSelected(aud.id), 'aud-unavail': !aud.isAvailable && !isSelected(aud.id) }"
                                    @click="toggleAuditeur(aud)">
                                    <div class="agc agc-avatar">
                                        <div class="aud-avatar" :class="'color-'+colorIdx(aud.audit_code)">{{ (aud.last_name||'?').charAt(0) }}</div>
                                    </div>
                                    <div class="agc agc-info">
                                        <div class="aud-name">{{ aud.last_name }} {{ aud.first_name }}</div>
                                        <div class="aud-code">{{ aud.audit_code }}</div>
                                    </div>
                                    <div class="agc" @click.stop>
                                        <select v-model="aud._role" class="role-select" @change="updateAuditeurRole(aud)" :disabled="aud.roleLocked">
                                            <option value="">—</option>
                                            <option v-for="r in rolesOrdered" :key="r.id" :value="r.code">{{ r.code }}</option>
                                        </select>
                                    </div>
                                    <div class="agc" @click.stop>
                                        <select v-if="needsParent(aud._role)" v-model="aud._parentId" class="parent-select"
                                            @change="updateAuditeurParent(aud)"
                                            :disabled="getSuperiors(aud._role).length === 0 || aud.roleLocked">
                                            <option :value="null">—</option>
                                            <option v-for="sup in getSuperiors(aud._role)" :key="'s-'+sup.auditeur_id" :value="sup.auditeur_id">{{ sup.audit_code }}</option>
                                        </select>
                                        <span v-else class="no-parent">—</span>
                                    </div>
                                    <div class="agc" @click.stop="showCompetenciesDetails(aud)">
                                        <span class="comp-count" :class="{ 'comp-count-zero': matchedCompetenciesCount(aud.id) === 0 }">
                                            {{ matchedCompetenciesCount(aud.id) }}<span class="comp-total" v-if="form.mission_id">/{{ totalRequiredCompetencies }}</span>
                                        </span>
                                    </div>
                                    <div class="agc" @click.stop="showUnavailabilityDetails(aud)">
                                        <div class="cal-pill" :class="aud.isAvailable ? 'cal-ok':'cal-warn'">
                                            <i :class="aud.isAvailable ? 'ti ti-calendar-check':'ti ti-calendar-x'"></i>
                                            <span v-if="!aud.isAvailable" class="cal-count">{{ aud.totalUnavailableDays }}</span>
                                        </div>
                                    </div>
                                    <div class="agc">
                                        <div class="check-box" :class="{ checked: isSelected(aud.id), 'check-warn': isSelected(aud.id) && !aud.isAvailable }">
                                            <i v-if="isSelected(aud.id)" class="ti ti-check"></i>
                                        </div>
                                    </div>
                                    <div class="agc" @click.stop="toggleRoleLock(aud)">
                                        <i :class="aud.roleLocked ? 'ti ti-lock':'ti ti-lock-open'" class="lock-icon"></i>
                                    </div>
                                </div>
                            </template>
                            <div v-if="!filteredAuditeurs.length" class="empty-aud">
                                <i class="ti ti-mood-empty"></i><span>Aucun auditeur trouvé</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Budget global récapitulatif -->
                <div class="pro-card">
                    <div class="pro-card-header">
                        <i class="ti ti-chart-pie me-2 text-primary"></i>
                        <span>Budget global de la mission</span>
                    </div>
                    <div class="pro-card-body">
                        <div class="budget-total-display">
                            <span class="btd-label">Montant total mission</span>
                            <span class="btd-value">{{ fmt(montantTotalMission) }} <small>FCFA</small></span>
                        </div>
                        <div class="scroll-zone scroll-zone-breakdown mt-3">
                            <div class="budget-breakdown">
                                <div class="bb-row">
                                    <span class="bb-dot" style="background:#3b82f6;"></span>
                                    <span class="bb-label">Budget fixe</span>
                                    <span class="bb-val">{{ fmt(form.montant_fixe || 0) }}</span>
                                </div>
                                <template v-for="(line, i) in budgetLines" :key="'bb-'+i">
                                    <div v-if="Number(line.montant) > 0" class="bb-row">
                                        <span class="bb-dot" :style="{ background: line.isCustom ? '#10b981':'#8b5cf6' }"></span>
                                        <span class="bb-label">{{ line.isCustom ? line.custom_label : line.libelle }}</span>
                                        <span class="bb-val">{{ fmt(line.montant) }}</span>
                                    </div>
                                </template>
                                <div class="bb-row">
                                    <span class="bb-dot" style="background:#10b981;"></span>
                                    <span class="bb-label">Budget auditeurs</span>
                                    <span class="bb-val">{{ fmt(totalBudgetAuditeurs) }}</span>
                                </div>
                            </div>
                        </div>
                        <div v-if="montantTotalMission > 0" class="budget-bars mt-3">
                            <div class="bbar">
                                <div class="bbar-seg" style="background:#3b82f6;" :style="{ width: pct(form.montant_fixe||0, montantTotalMission)+'%' }"></div>
                                <div class="bbar-seg" style="background:#8b5cf6;" :style="{ width: pct(totalBudgetVariable, montantTotalMission)+'%' }"></div>
                                <div class="bbar-seg" style="background:#10b981;" :style="{ width: pct(totalBudgetAuditeurs, montantTotalMission)+'%' }"></div>
                            </div>
                            <div class="bbar-legend">
                                <span><i style="background:#3b82f6;"></i>Fixe</span>
                                <span><i style="background:#8b5cf6;"></i>Variable</span>
                                <span><i style="background:#10b981;"></i>Auditeurs</span>
                            </div>
                        </div>
                    </div>
                    <div class="pro-card-footer">
                        <button class="pro-btn pro-btn-ghost" @click="cancel">
                            <i class="ti ti-circle-minus me-1"></i>Annuler
                        </button>
                        <button class="pro-btn pro-btn-primary"
                            :disabled="form.processing || !canSubmit"
                            :class="{ 'pro-btn-disabled': form.processing || !canSubmit }"
                            @click="submit">
                            <span v-if="form.processing" class="spinner-border spinner-border-sm me-1"></span>
                            <i v-else class="ti ti-device-floppy me-1"></i>
                            Valider la programmation
                        </button>
                    </div>
                </div>
            </b-col>
        </b-row>

        <!-- ░░ MODAL MISSIONS ░░ -->
        <teleport to="body">
            <transition name="mfade">
                <div v-if="showMissionModal" class="pro-modal-overlay" @click.self="showMissionModal = false">
                    <div class="pro-modal">
                        <div class="pro-modal-header">
                            <div class="d-flex align-items-center gap-3">
                                <div class="modal-icon-wrap"><i class="ti ti-clipboard-list"></i></div>
                                <div>
                                    <h5 class="mb-0">Sélection de la mission</h5>
                                    <small>Les entités liées seront chargées automatiquement</small>
                                </div>
                            </div>
                            <button class="pro-modal-close" @click="showMissionModal = false"><i class="ti ti-x"></i></button>
                        </div>
                        <div class="pro-modal-filters">
                            <div class="search-box-lg"><i class="ti ti-search"></i><input v-model="modalSearch" placeholder="Rechercher une mission…" /></div>
                            <select v-model="modalPriorityFilter" class="pro-input" style="width:140px;">
                                <option :value="null">Toutes priorités</option>
                                <option value="critique">Critique</option>
                                <option value="haute">Haute</option>
                                <option value="moyenne">Moyenne</option>
                                <option value="basse">Basse</option>
                            </select>
                            <select v-model="modalStatusFilter" class="pro-input" style="width:130px;">
                                <option :value="null">Tous statuts</option>
                                <option value="brouillon">Brouillon</option>
                                <option value="planifiée">Planifiée</option>
                                <option value="proposed">Proposée</option>
                            </select>
                        </div>
                        <div class="pro-modal-body scroll-zone scroll-zone-modal">
                            <div class="modal-result-count">{{ filteredMissionsModal.length }} mission(s) disponible(s)</div>
                            <table class="modal-table">
                                <thead class="sticky-thead-dark">
                                    <tr>
                                        <th>Code</th><th>N° FPM</th><th>Titre</th><th>Objectif</th>
                                        <th>Priorité</th><th>Période prévue</th><th>Statut</th><th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="m in filteredMissionsModal" :key="m.id"
                                        @dblclick="selectMission(m)"
                                        :class="{ 'modal-row-active': form.mission_id == m.id }">
                                        <td><span class="code-chip">{{ m.code }}</span></td>
                                        <td style="font-size:.7rem;color:#94a3b8;">{{ m.fpm_number || '—' }}</td>
                                        <td class="fw-semibold" style="font-size:.75rem;">{{ truncate(m.title, 30) }}</td>
                                        <td style="font-size:.7rem;color:#94a3b8;">{{ truncate(m.objective || m.but || '', 28) }}</td>
                                        <td><span class="priority-badge" :class="'prio-'+(m.priority||'').toLowerCase()">{{ m.priority || '—' }}</span></td>
                                        <td style="font-size:.7rem;white-space:nowrap;">{{ fmtDate(m.planned_start_date) }} → {{ fmtDate(m.planned_end_date) }}</td>
                                        <td><span class="status-chip">{{ m.status }}</span></td>
                                        <td><button class="pro-btn pro-btn-sm pro-btn-primary" @click.stop="selectMission(m)">Choisir</button></td>
                                    </tr>
                                    <tr v-if="!filteredMissionsModal.length">
                                        <td colspan="8" class="empty-row"><i class="ti ti-mood-empty"></i><span>Aucune mission disponible</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="pro-modal-footer">
                            <span style="font-size:.75rem;color:#94a3b8;">{{ filteredMissionsModal.length }} résultat(s)</span>
                            <button class="pro-btn pro-btn-ghost" @click="showMissionModal = false">Fermer</button>
                        </div>
                    </div>
                </div>
            </transition>
        </teleport>

        <!-- ░░ MODAL INDISPONIBILITÉS ░░ -->
        <teleport to="body">
            <transition name="mfade">
                <div v-if="showUnavailabilityModal" class="pro-modal-overlay" @click.self="showUnavailabilityModal = false">
                    <div class="pro-modal" style="max-width:600px;">
                        <div class="pro-modal-header">
                            <div class="d-flex align-items-center gap-3">
                                <div class="modal-icon-wrap"><i class="ti ti-calendar-x"></i></div>
                                <div>
                                    <h5 class="mb-0">Indisponibilités — {{ selectedAuditorForUnavail?.last_name }}</h5>
                                    <small>Détail des conflits par entité</small>
                                </div>
                            </div>
                            <button class="pro-modal-close" @click="showUnavailabilityModal = false"><i class="ti ti-x"></i></button>
                        </div>
                        <div class="pro-modal-body scroll-zone scroll-zone-modal">
                            <div v-if="selectedAuditorForUnavail">
                                <div v-if="selectedAuditorForUnavail.conflictingPeriods.length > 0">
                                    <p class="mb-2" style="font-size:.8rem;"><strong>{{ selectedAuditorForUnavail.conflictingPeriods.length }}</strong> période(s) de conflit :</p>
                                    <div class="unavail-list">
                                        <div v-for="(p, idx) in selectedAuditorForUnavail.conflictingPeriods" :key="idx"
                                            class="unavail-item" :style="{ borderLeftColor: p.type_color }">
                                            <div class="unavail-header">
                                                <span class="unavail-type" :style="{ backgroundColor: p.type_color+'20', color: p.type_color }">
                                                    <i class="ti ti-calendar-x" style="margin-right:4px;"></i>
                                                    {{ p.type_name || p.type }}
                                                    <span v-if="p.isGlobal" class="unavail-global-badge">Global</span>
                                                </span>
                                                <span class="unavail-duration">{{ daysBetween(p.date_start, p.date_end) }} jours</span>
                                            </div>
                                            <div class="unavail-dates"><i class="ti ti-calendar"></i> Du {{ fmtDate(p.date_start) }} au {{ fmtDate(p.date_end) }}</div>
                                            <div v-if="p.reason" class="unavail-reason"><i class="ti ti-message"></i> {{ p.reason }}</div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center text-muted py-4">
                                    <i class="ti ti-calendar-check" style="font-size:3rem;opacity:.3;display:block;margin-bottom:8px;"></i>
                                    Aucune indisponibilité pour cet auditeur sur les périodes affectées.
                                </div>
                            </div>
                        </div>
                        <div class="pro-modal-footer">
                            <span></span>
                            <button class="pro-btn pro-btn-ghost" @click="showUnavailabilityModal = false">Fermer</button>
                        </div>
                    </div>
                </div>
            </transition>
        </teleport>

        <!-- ░░ MODAL COMPÉTENCES ░░ -->
        <teleport to="body">
            <transition name="mfade">
                <div v-if="showCompetenciesModal" class="pro-modal-overlay" @click.self="showCompetenciesModal = false">
                    <div class="pro-modal" style="max-width:600px;">
                        <div class="pro-modal-header">
                            <div class="d-flex align-items-center gap-3">
                                <div class="modal-icon-wrap"><i class="ti ti-certificate"></i></div>
                                <div>
                                    <h5 class="mb-0">Compétences — {{ selectedAuditorForCompetencies?.last_name }}</h5>
                                    <small>Mission : {{ form.code_mission }}</small>
                                </div>
                            </div>
                            <button class="pro-modal-close" @click="showCompetenciesModal = false"><i class="ti ti-x"></i></button>
                        </div>
                        <div class="pro-modal-body scroll-zone scroll-zone-modal">
                            <div v-if="selectedAuditorForCompetencies">
                                <div v-if="requiredCompetencies.length > 0" class="comp-list">
                                    <div v-for="req in requiredCompetencies" :key="req.competency_id" class="comp-item">
                                        <div class="comp-header">
                                            <span class="comp-code">{{ req.competency_code }}</span>
                                            <span class="comp-name">{{ req.competency_name }}</span>
                                            <span class="comp-level-req">Requis : {{ req.minimum_level }}</span>
                                        </div>
                                        <div class="comp-auditor">
                                            <template v-if="auditorHasCompetency(selectedAuditorForCompetencies.id, req)">
                                                <span class="comp-status success">
                                                    <i class="ti ti-check"></i> Possédé (niveau {{ getAuditorCompetencyLevel(selectedAuditorForCompetencies.id, req.competency_id) }})
                                                </span>
                                            </template>
                                            <template v-else>
                                                <span class="comp-status danger"><i class="ti ti-x"></i> Non possédé</span>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-muted text-center py-4" style="font-size:.8rem;">
                                    Aucune compétence requise pour cette mission.
                                </div>
                            </div>
                        </div>
                        <div class="pro-modal-footer">
                            <span></span>
                            <button class="pro-btn pro-btn-ghost" @click="showCompetenciesModal = false">Fermer</button>
                        </div>
                    </div>
                </div>
            </transition>
        </teleport>

    </VerticalLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useForm, router, Head } from '@inertiajs/vue3'
import axios from 'axios'
import VerticalLayout from '@/layouts/VerticalLayout.vue'

const props = defineProps({
    auditeurs:               { type: Array,  default: () => [] },
    phases:                  { type: Array,  default: () => [] },
    typesBudget:             { type: Array,  default: () => [] },
    roles:                   { type: Array,  default: () => [] },
    budgetCategories:        { type: Array,  default: () => [] },
    missions:                { type: Array,  default: () => [] },
    entities:                { type: Array,  default: () => [] },
    newCode:                 { type: String, default: '' },
    missionCompetencies:     { type: Object, default: () => ({}) },
    auditorCompetencies:     { type: Object, default: () => ({}) },
    auditorUnavailabilities: { type: Object, default: () => ({}) },
    globalUnavailabilities:  { type: Array,  default: () => [] },
})

// ─── FORM ───────────────────────────────────────────────────────────────────
const form = useForm({
    mission_id:         null,
    code_programmation: props.newCode || '',
    code_mission:       '',
    fpm_number:         '',
    libelle:            '666',
    objectif:           '',
    domain:             '',
    priority:           '',
    mission_status:     '',
    lieux:              '',
    date_debut:         '',
    date_fin:           '',
    montant_fixe:       0,
    auditeurs:          [],
    budget_lines:       [],
    entity_periods:     [],   // ← nouvelle clé : remplace missions_planifiees
})

// ─── ENTITÉS — NOUVELLE LOGIQUE ──────────────────────────────────────────────
// entityPeriods: tableau de { entity_id, entity_name, entity_code,
//                             planned_start_date, planned_end_date, hasDateError }
// Chargé depuis audit_mission_entities via API lors de la sélection d'une mission

const entityPeriods   = ref([])
const loadingEntities = ref(false)
const missionDates    = ref({ start: null, end: null })  // dates originales pour reset

/**
 * Appel API : charge les entités liées à la mission depuis audit_mission_entities
 */
async function loadMissionEntities(missionId) {
    if (!missionId) { entityPeriods.value = []; return }
    loadingEntities.value = true
    try {
        const { data } = await axios.get(
            route('audit.core.programmation-missions.entities', missionId)
        )
        if (data.success) {
            entityPeriods.value = (data.entities || []).map(e => ({
                entity_id:          e.entity_id,
                entity_name:        e.entity_name,
                entity_code:        e.entity_code || '',
                planned_start_date: e.planned_start_date || '',
                planned_end_date:   e.planned_end_date   || '',
                hasDateError:       false,
            }))
            // Mémoriser les dates de la mission pour le reset
            if (data.mission) {
                missionDates.value = {
                    start: data.mission.planned_start_date,
                    end:   data.mission.planned_end_date,
                }
            }
            // Recalculer disponibilités auditeurs avec les nouvelles périodes
            auditeursSelectionnes.value.forEach(a => updateAuditeurDispo(a))
        }
    } catch (err) {
        console.error('Erreur chargement entités mission :', err)
        entityPeriods.value = []
    } finally {
        loadingEntities.value = false
    }
}

/** Validation date d'une entité + recalcul dispo */
function onEntityDateChange(ep) {
    if (ep.planned_start_date && ep.planned_end_date) {
        ep.hasDateError = new Date(ep.planned_end_date) < new Date(ep.planned_start_date)
    } else {
        ep.hasDateError = false
    }
    auditeursSelectionnes.value.forEach(a => updateAuditeurDispo(a))
}

/** Réinitialise toutes les entités avec les dates de la mission */
function resetAllDates() {
    entityPeriods.value.forEach(ep => {
        ep.planned_start_date = missionDates.value.start || ''
        ep.planned_end_date   = missionDates.value.end   || ''
        ep.hasDateError       = false
    })
    auditeursSelectionnes.value.forEach(a => updateAuditeurDispo(a))
}

/** Période globale = min(start) / max(end) de toutes les entités */
const globalPeriod = computed(() => {
    const validEps = entityPeriods.value.filter(ep => ep.planned_start_date && ep.planned_end_date && !ep.hasDateError)
    if (!validEps.length) return { start: null, end: null, duration: 0 }
    const starts   = validEps.map(ep => ep.planned_start_date).sort()
    const ends     = validEps.map(ep => ep.planned_end_date).sort()
    const start    = starts[0]
    const end      = ends[ends.length - 1]
    return { start, end, duration: calcDuration(start, end) }
})

/** Vérifie chevauchement entre deux entités (même période) */
function entityHasOverlap(ep) {
    if (!ep.planned_start_date || !ep.planned_end_date || ep.hasDateError) return false
    return entityPeriods.value.some(other => {
        if (other.entity_id === ep.entity_id || !other.planned_start_date || !other.planned_end_date) return false
        return datesOverlap(ep.planned_start_date, ep.planned_end_date, other.planned_start_date, other.planned_end_date)
    })
}
const hasEntityOverlap = computed(() => entityPeriods.value.some(ep => entityHasOverlap(ep)))

// Couleurs entités (rotatif palette)
const ENTITY_COLORS = ['#3b82f6','#10b981','#f59e0b','#8b5cf6','#ef4444','#0ea5e9','#ec4899','#14b8a6']
function entityColor(idx) { return ENTITY_COLORS[idx % ENTITY_COLORS.length] }

// IDs de toutes les entités actives
const allEntityIds = computed(() => entityPeriods.value.map(ep => ep.entity_id))

// ─── SÉLECTION MISSION ──────────────────────────────────────────────────────
const showMissionModal   = ref(false)
const modalSearch        = ref('')
const modalPriorityFilter = ref(null)
const modalStatusFilter   = ref(null)

function selectMission(m) {
    form.mission_id     = m.id
    form.code_mission   = m.code || ''
    form.fpm_number     = m.fpm_number || ''
    form.libelle        = m.title || ''
    form.objectif       = m.objective || m.but || ''
    form.domain         = m.domain || ''
    form.priority       = m.priority || ''
    form.mission_status = m.status || ''
    form.date_debut     = m.planned_start_date || ''
    form.date_fin       = m.planned_end_date   || ''
    showMissionModal.value = false
    // ← Charge les entités liées depuis audit_mission_entities
    loadMissionEntities(m.id)
}

function clearMission() {
    form.mission_id = null; form.code_mission = ''; form.fpm_number = ''
    form.libelle = ''; form.objectif = ''; form.domain = ''
    form.priority = ''; form.mission_status = ''; form.date_debut = ''; form.date_fin = ''
    entityPeriods.value = []
    missionDates.value  = { start: null, end: null }
    auditeursSelectionnes.value.forEach(a => {
        a.affectations   = {}
        a.dispoParEntite = {}
    })
}

const filteredMissionsModal = computed(() => {
    let list = props.missions || []
    if (modalPriorityFilter.value) list = list.filter(m => m.priority === modalPriorityFilter.value)
    if (modalStatusFilter.value)   list = list.filter(m => m.status   === modalStatusFilter.value)
    if (modalSearch.value.trim()) {
        const s = modalSearch.value.toLowerCase()
        list = list.filter(m =>
            (m.code  || '').toLowerCase().includes(s) ||
            (m.title || '').toLowerCase().includes(s)
        )
    }
    return list
})

// ─── ALERTES ────────────────────────────────────────────────────────────────
const selectionWarning = ref(null)
let warningTimer = null
function showWarning(msg) {
    selectionWarning.value = msg
    clearTimeout(warningTimer)
    warningTimer = setTimeout(() => { selectionWarning.value = null }, 5000)
}

// ─── AUDITEURS ───────────────────────────────────────────────────────────────
const auditeursSelectionnes  = ref([])
const budgetLines            = ref([])
const activeBudgetAuditeur   = ref(0)
const audSearch              = ref('')
const activeSelIdx           = ref(-1)
const showUnavailabilityModal       = ref(false)
const selectedAuditorForUnavail     = ref(null)
const showCompetenciesModal         = ref(false)
const selectedAuditorForCompetencies = ref(null)

const rolesOrdered = computed(() =>
    [...(props.roles || [])].sort((a, b) => (a.niveau ?? 99) - (b.niveau ?? 99))
)

// Quand les entités changent, synchroniser affectations & budgetLines des auditeurs
watch(allEntityIds, (newIds, oldIds) => {
    if (!auditeursSelectionnes.value.length) return
    const added   = newIds.filter(id => !oldIds.includes(id))
    const removed = oldIds.filter(id => !newIds.includes(id))
    if (!added.length && !removed.length) return

    auditeursSelectionnes.value.forEach(aud => {
        added.forEach(id => { aud.affectations[id] = true; aud.dispoParEntite[id] = true })
        removed.forEach(id => { delete aud.affectations[id]; delete aud.dispoParEntite[id] })
        aud.budgetLines.forEach(line => {
            added.forEach(id => { line.montants[id] = 0 })
            removed.forEach(id => { delete line.montants[id] })
        })
        updateAuditeurDispo(aud)
    })
})

// Liste enrichie des auditeurs disponibles (statut, disponibilités calculées)
const enhancedAuditeursList = ref([])
function buildEnhancedList() {
    return (props.auditeurs || []).map(a => ({
        ...a, _role: '', _parentId: null, roleLocked: false,
        isAvailable: true, conflictingPeriods: [], totalUnavailableDays: 0,
    }))
}
function refreshAuditeurs() { enhancedAuditeursList.value = buildEnhancedList() }
watch(() => props.auditeurs, refreshAuditeurs, { immediate: true })
onMounted(refreshAuditeurs)

const filteredAuditeurs = computed(() => {
    let list = enhancedAuditeursList.value
    const t  = (audSearch.value || '').trim().toLowerCase()
    if (t) list = list.filter(a =>
        (a.audit_code || '').toLowerCase().includes(t) ||
        (a.last_name  || '').toLowerCase().includes(t) ||
        (a.first_name || '').toLowerCase().includes(t)
    )
    return [...list].sort((a, b) => {
        const score = x => isSelected(x.id) ? 0 : x.isAvailable ? 1 : 2
        return score(a) - score(b)
    })
})

function isSelected(id) { return auditeursSelectionnes.value.some(a => a.auditeur_id == id) }

function toggleAuditeur(aud) {
    const idx = auditeursSelectionnes.value.findIndex(a => a.auditeur_id == aud.id)
    if (idx >= 0) {
        auditeursSelectionnes.value.splice(idx, 1)
        if (activeBudgetAuditeur.value >= auditeursSelectionnes.value.length)
            activeBudgetAuditeur.value = Math.max(0, auditeursSelectionnes.value.length - 1)
        return
    }
    // Nouveau auditeur : affectation par défaut sur toutes les entités
    const affectations   = Object.fromEntries(allEntityIds.value.map(id => [id, true]))
    const dispoParEntite = Object.fromEntries(allEntityIds.value.map(id => [id, true]))
    const nouvelAuditeur = {
        auditeur_id: aud.id, audit_code: aud.audit_code, last_name: aud.last_name,
        first_name: aud.first_name, role: '', parent_auditeur_id: null, budget: 0,
        parent_code: null, isAvailable: true, totalUnavailableDays: 0,
        conflictingPeriods: [], affectations, dispoParEntite,
        budgetLines: makeAuditeurBudgetLines(),
    }
    auditeursSelectionnes.value.push(nouvelAuditeur)
    activeBudgetAuditeur.value = auditeursSelectionnes.value.length - 1
    updateAuditeurDispo(nouvelAuditeur)
}

function deselectAuditeur(aud) {
    const idx = auditeursSelectionnes.value.findIndex(a => a.auditeur_id === aud.auditeur_id)
    if (idx >= 0) auditeursSelectionnes.value.splice(idx, 1)
}

/**
 * Calcule la disponibilité d'un auditeur pour chaque entité
 * en utilisant la PÉRIODE PROPRE à chaque entité (nouvelle logique).
 */
function updateAuditeurDispo(aud) {
    const dispoParEntite = Object.fromEntries(allEntityIds.value.map(id => [id, true]))
    let totalConflicts   = 0
    let allConflicts     = []

    entityPeriods.value.forEach(ep => {
        if (!aud.affectations[ep.entity_id]) return
        if (!ep.planned_start_date || !ep.planned_end_date || ep.hasDateError) return

        const conflicts = getConflictingPeriods(aud.auditeur_id, ep.planned_start_date, ep.planned_end_date)
        if (conflicts.length) {
            dispoParEntite[ep.entity_id] = false
            totalConflicts += conflicts.reduce((s, c) => s + daysBetween(c.date_start, c.date_end), 0)
            allConflicts    = allConflicts.concat(conflicts)
        }
    })

    aud.dispoParEntite       = dispoParEntite
    aud.isAvailable          = totalConflicts === 0
    aud.totalUnavailableDays = totalConflicts
    aud.conflictingPeriods   = allConflicts
}

function getConflictingPeriods(auditorId, startDate, endDate) {
    if (!startDate || !endDate) return []
    const mStart = new Date(startDate)
    const mEnd   = new Date(endDate)
    return [
        ...(props.auditorUnavailabilities[auditorId] || []).map(p => ({ ...p, isGlobal: false })),
        ...(props.globalUnavailabilities || []).map(p => ({ ...p, isGlobal: true })),
    ].filter(p => new Date(p.date_start) <= mEnd && new Date(p.date_end) >= mStart)
}

/** Tooltip de disponibilité d'un auditeur pour une entité */
function tooltipContent(aud, entityId) {
    if (aud.dispoParEntite[entityId]) return 'Disponible pour cette entité'
    const ep = entityPeriods.value.find(e => e.entity_id === entityId)
    if (!ep?.planned_start_date) return 'Indisponible'
    const conflicts = getConflictingPeriods(aud.auditeur_id, ep.planned_start_date, ep.planned_end_date)
    if (!conflicts.length) return 'Indisponible (cause inconnue)'
    return 'Indisponible :\n' + conflicts.map(p =>
        `• ${p.type_name || p.type} : du ${fmtDate(p.date_start)} au ${fmtDate(p.date_end)}` +
        (p.reason ? ` (${p.reason})` : '')
    ).join('\n')
}

// ─── RÔLES & HIÉRARCHIE ─────────────────────────────────────────────────────
function getByRole(role)             { return auditeursSelectionnes.value.filter(a => a.role === role) }
function getChildrenOf(parentId, role) { return auditeursSelectionnes.value.filter(a => a.role === role && a.parent_auditeur_id == parentId) }
function needsParent(role)           { return role === 'AS' || role === 'AJ' }
function getSuperiors(role) {
    if (role === 'AS') return auditeursSelectionnes.value.filter(a => a.role === 'CM')
    if (role === 'AJ') return auditeursSelectionnes.value.filter(a => a.role === 'AS')
    return []
}

function updateAuditeurRole(aud) {
    const sel = auditeursSelectionnes.value.find(a => a.auditeur_id == aud.id)
    if (!sel) return
    const newRole = aud._role; const oldRole = sel.role
    if (newRole === oldRole) return
    if (newRole === 'DM' && auditeursSelectionnes.value.some(a => a.role === 'DM' && a.auditeur_id != aud.id)) {
        showWarning('Un seul DM autorisé.'); aud._role = oldRole; return
    }
    if (newRole === 'CM' && auditeursSelectionnes.value.some(a => a.role === 'CM' && a.auditeur_id != aud.id)) {
        showWarning('Un seul CM autorisé.'); aud._role = oldRole; return
    }
    sel.role = newRole
    if (!needsParent(newRole) || (aud._parentId && !getSuperiors(newRole).some(s => s.auditeur_id == aud._parentId))) {
        aud._parentId = null; sel.parent_auditeur_id = null; sel.parent_code = null
    }
}

function updateAuditeurParent(aud) {
    const sel = auditeursSelectionnes.value.find(a => a.auditeur_id == aud.id)
    if (!sel) return
    const parentId = aud._parentId
    if (parentId && !getSuperiors(sel.role).some(s => s.auditeur_id == parentId)) {
        showWarning('Parent invalide.'); aud._parentId = null; sel.parent_auditeur_id = null; sel.parent_code = null; return
    }
    sel.parent_auditeur_id = parentId
    sel.parent_code = parentId ? (auditeursSelectionnes.value.find(a => a.auditeur_id == parentId)?.audit_code || null) : null
}

function toggleRoleLock(aud) { aud.roleLocked = !aud.roleLocked }

// ─── COMPÉTENCES ────────────────────────────────────────────────────────────
const requiredCompetencies      = computed(() => form.mission_id ? (props.missionCompetencies[form.mission_id] || []) : [])
const totalRequiredCompetencies = computed(() => requiredCompetencies.value.length)

function auditorHasCompetency(auditorId, req) {
    return (props.auditorCompetencies[auditorId] || []).some(ac => ac.competency_id === req.competency_id && ac.level >= req.minimum_level)
}
function getAuditorCompetencyLevel(auditorId, competencyId) {
    const found = (props.auditorCompetencies[auditorId] || []).find(ac => ac.competency_id === competencyId)
    return found ? found.level : 0
}
function matchedCompetenciesCount(auditorId) {
    if (!form.mission_id) return 0
    return requiredCompetencies.value.filter(req => auditorHasCompetency(auditorId, req)).length
}

function showUnavailabilityDetails(aud)  { selectedAuditorForUnavail.value = aud;    showUnavailabilityModal.value = true }
function showCompetenciesDetails(aud)    { selectedAuditorForCompetencies.value = aud; showCompetenciesModal.value  = true }

// ─── BUDGET ─────────────────────────────────────────────────────────────────
function makeBudgetLines() {
    const lines = (props.budgetCategories || []).map(c => ({
        category_id: c.id, libelle: c.libelle, custom_label: '',
        montant: Number(c.montant_defaut ?? 0) || 0, isCustom: false,
    }))
    lines.push({ category_id: null, libelle: '', custom_label: 'Nouvelle ligne', montant: 0, isCustom: true })
    return lines
}

function makeAuditeurBudgetLines() {
    const entityIds = allEntityIds.value
    const lines = (props.budgetCategories || []).map(c => ({
        category_id: c.id, libelle: c.libelle, custom_label: '', isCustom: false,
        montants: Object.fromEntries(entityIds.map(id => [id, 0])),
    }))
    lines.push({
        category_id: null, libelle: '', custom_label: 'Nouvelle ligne', isCustom: true,
        montants: Object.fromEntries(entityIds.map(id => [id, 0])),
    })
    return lines
}

watch(() => props.budgetCategories, () => { budgetLines.value = makeBudgetLines() }, { immediate: true })

function addCustomBudgetLine() {
    budgetLines.value.push({ category_id: null, libelle: '', custom_label: 'Nouvelle ligne', montant: 0, isCustom: true })
}
function removeBudgetLine(idx) { budgetLines.value.splice(idx, 1) }

function addAuditeurCustomBudgetLine(audIdx) {
    const aud = auditeursSelectionnes.value[audIdx]
    if (aud) {
        aud.budgetLines.push({
            category_id: null, libelle: '', custom_label: 'Nouvelle ligne', isCustom: true,
            montants: Object.fromEntries(allEntityIds.value.map(id => [id, 0])),
        })
    }
}
function removeAuditeurBudgetLine(audIdx, lineIdx) {
    const aud = auditeursSelectionnes.value[audIdx]
    if (aud) { aud.budgetLines.splice(lineIdx, 1); updateAuditeurBudget(audIdx) }
}

function updateAuditeurBudget(idx) {
    const aud = auditeursSelectionnes.value[idx]
    if (aud) aud.budget = getTotalBudgetAuditeur(aud)
}

function getTotalBudgetAuditeur(aud) {
    return aud.budgetLines.reduce((s, l) => s + Object.values(l.montants).reduce((a, v) => a + (Number(v)||0), 0), 0)
}
function getTotalBudgetAuditeurByEntity(aud, entityId) {
    return aud.budgetLines.reduce((s, l) => s + (Number(l.montants[entityId])||0), 0)
}

const totalBudgetVariable  = computed(() => budgetLines.value.reduce((s, l) => s + (Number(l.montant)||0), 0))
const totalBudgetAuditeurs = computed(() => auditeursSelectionnes.value.reduce((s, a) => s + getTotalBudgetAuditeur(a), 0))
const montantTotalMission  = computed(() => (Number(form.montant_fixe)||0) + totalBudgetVariable.value + totalBudgetAuditeurs.value)

// ─── VALIDATION ─────────────────────────────────────────────────────────────
const canSubmit = computed(() => {
    if (!form.mission_id || !form.code_programmation) return false
    if (!entityPeriods.value.length) return false
    // Toutes les entités doivent avoir des dates valides
    if (entityPeriods.value.some(ep => !ep.planned_start_date || !ep.planned_end_date || ep.hasDateError)) return false
    return true
})

// ─── SUBMIT ─────────────────────────────────────────────────────────────────
function cancel() { router.get(route('audit.core.programmation-missions.index')) }

function submit() {
    if (!canSubmit.value) {
        showWarning('Veuillez compléter toutes les dates des entités avant de valider.')
        return
    }

    // Période globale = min(start)/max(end)
    const starts = entityPeriods.value.map(ep => ep.planned_start_date).filter(Boolean).sort()
    const ends   = entityPeriods.value.map(ep => ep.planned_end_date).filter(Boolean).sort()
    form.date_debut = starts[0] || ''
    form.date_fin   = ends[ends.length - 1] || ''

    // entity_periods → backend
    form.entity_periods = entityPeriods.value.map(ep => ({
        entity_id:          ep.entity_id,
        planned_start_date: ep.planned_start_date,
        planned_end_date:   ep.planned_end_date,
    }))

    // Budget global (uniquement lignes avec montant > 0)
    form.budget_lines = budgetLines.value
        .filter(l => Number(l.montant) > 0)
        .map(l => ({ category_id: l.category_id, custom_label: l.custom_label, montant: l.montant }))

    // Auditeurs + budget par entité
    form.auditeurs = auditeursSelectionnes.value.map(a => ({
        auditeur_id:        a.auditeur_id,
        role:               a.role,
        parent_auditeur_id: a.parent_auditeur_id || null,
        affectations:       Object.keys(a.affectations).filter(eid => a.affectations[eid]).map(Number),
        budget_lines:       [],
    }))

    auditeursSelectionnes.value.forEach((a, idx) => {
        a.budgetLines.forEach(line => {
            Object.entries(line.montants).forEach(([entityId, montant]) => {
                if (Number(montant) > 0) {
                    form.auditeurs[idx].budget_lines.push({
                        category_id:  line.category_id,
                        custom_label: line.custom_label,
                        entity_id:    parseInt(entityId),
                        montant:      Number(montant),
                    })
                }
            })
        })
    })

    form.post(route('audit.core.programmation-missions.store'), { preserveScroll: true })
}

// ─── UTILITAIRES ────────────────────────────────────────────────────────────
function calcDuration(start, end) {
    if (!start || !end) return 0
    return Math.max(0, Math.ceil((new Date(end) - new Date(start)) / 86400000) + 1)
}
function datesOverlap(s1, e1, s2, e2) {
    return new Date(s1) <= new Date(e2) && new Date(s2) <= new Date(e1)
}
function daysBetween(start, end) {
    if (!start || !end) return 0
    return Math.max(0, Math.ceil((new Date(end) - new Date(start)) / (1000*60*60*24)) + 1)
}
function truncate(s, n = 30) { return (s && s.length > n) ? s.slice(0, n) + '…' : (s || '—') }
function fmt(v)      { return (!isNaN(v) && v != null) ? Number(v).toLocaleString('fr-FR') : '0' }
function fmtShort(v) {
    if (!v) return '0'
    if (v >= 1000000) return (v/1000000).toFixed(1) + 'M'
    if (v >= 1000)    return (v/1000).toFixed(0) + 'k'
    return String(v)
}
function fmtDate(d) {
    if (!d) return '—'
    const p = String(d).split('-')
    return p.length === 3 ? `${p[2]}/${p[1]}/${p[0]}` : d
}
function pct(part, total) { if (!total) return 0; return Math.min(100, Math.round((Number(part)/Number(total))*100)) }
function colorIdx(code) { return ((code || '').charCodeAt(0) || 0) % 6 }
</script>

<style scoped>
/* ═══════════════════════════════════════════════════════════════════════════
   GLOBAL LAYOUT
   ═══════════════════════════════════════════════════════════════════════════ */
.page-header { background: linear-gradient(135deg,#f0f4ff 0%,#f8faff 100%); border-radius:14px; padding:18px 22px; border:1px solid #e0eaff; }
.ph-icon     { width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#3b82f6,#6366f1);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.3rem;flex-shrink:0; }
.ph-title    { font-size:.95rem;font-weight:800;color:#1e293b; }
.ph-sub      { font-size:.72rem;color:#64748b; }
.badge-code  { font-size:.7rem;font-weight:700;font-family:monospace;background:#e0e7ff;color:#3730a3;padding:3px 10px;border-radius:8px; }
.badge-phase { font-size:.65rem;font-weight:800;background:#dbeafe;color:#1d4ed8;padding:3px 8px;border-radius:6px;letter-spacing:.05em; }

/* ═══════════════════════════════════════════════════════════════════════════
   PRO CARD
   ═══════════════════════════════════════════════════════════════════════════ */
.pro-card         { background:#fff;border-radius:12px;border:1px solid #e8edf4;box-shadow:0 2px 8px rgba(15,23,42,.06);overflow:hidden; }
.pro-card-header  { display:flex;align-items:center;padding:10px 14px;background:#f8faff;border-bottom:1px solid #e8edf4;font-size:.75rem;font-weight:700;color:#334155; }
.pro-card-body    { padding:12px 14px; }
.pro-card-footer  { display:flex;align-items:center;justify-content:flex-end;gap:10px;padding:10px 14px;border-top:1px solid #e8edf4;background:#f8faff; }
.count-badge      { background:#dbeafe;color:#1d4ed8;font-size:.62rem;font-weight:800;padding:1px 7px;border-radius:20px; }

/* ═══════════════════════════════════════════════════════════════════════════
   ENTITÉS × PÉRIODES (EPT)
   ═══════════════════════════════════════════════════════════════════════════ */
.ept-header, .ept-row {
    display: grid;
    grid-template-columns: 70px 1fr 130px 130px 54px 1fr;
    align-items: center;
    gap: 0;
}
.ept-header {
    background: #f1f5f9;
    border-bottom: 1px solid #e2e8f0;
    padding: 6px 12px;
}
.ept-col { padding: 4px 6px; }
.ept-header .ept-col { font-size: .62rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .04em; }
.ept-code  { }
.ept-name  { }
.ept-date  { min-width: 130px; }
.ept-dur   { min-width: 54px; text-align: center; }
.ept-dispo { }

.ept-row {
    padding: 6px 12px;
    border-bottom: 1px solid #f1f5f9;
    transition: background .15s;
}
.ept-row:last-of-type  { border-bottom: none; }
.ept-row:hover         { background: #f8faff; }
.ept-row-alt           { background: #fafbfc; }
.ept-row-invalid       { background: #fff5f5; }
.ept-row-overlap       { background: #fffbeb; }

.entity-code-badge {
    display: inline-block;
    font-size: .6rem; font-weight: 800; font-family: monospace;
    padding: 2px 7px; border-radius: 6px;
    border: 1px solid; white-space: nowrap;
}
.entity-dot  { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.entity-name-text { font-size: .72rem; color: #334155; }

.duration-mini {
    font-size: .68rem; font-weight: 700; color: #0ea5e9;
    background: #e0f2fe; border-radius: 6px; padding: 2px 6px;
    text-align: center;
}
.duration-warn { color: #ef4444; background: #fee2e2; }

/* Dispo pills par entité */
.dispo-pills { display: flex; flex-wrap: wrap; gap: 3px; }
.dispo-pill  {
    font-size: .58rem; font-weight: 700;
    padding: 1px 6px; border-radius: 10px;
    border: 1px solid;
}
.dispo-ok  { background: #dcfce7; color: #15803d; border-color: #bbf7d0; }
.dispo-ko  { background: #fee2e2; color: #dc2626; border-color: #fca5a5; }

/* Barres d'alerte */
.ept-error-bar, .ept-overlap-bar {
    margin: 0 12px 8px;
    border-radius: 8px;
    padding: 6px 12px;
    font-size: .7rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 6px;
}
.ept-error-bar   { background: #fee2e2; color: #b91c1c; }
.ept-overlap-bar { background: #fef3c7; color: #b45309; }

/* Résumé global bas du tableau */
.ept-global-summary {
    background: #f8faff;
    border-top: 1px solid #e8edf4;
    padding: 8px 12px;
}
.global-period-item   { display: flex; flex-direction: column; gap: 1px; }
.gpi-label            { font-size: .6rem; color: #94a3b8; font-weight: 600; text-transform: uppercase; }
.gpi-value            { font-size: .78rem; color: #1e293b; font-weight: 700; }

/* Badge période dans l'en-tête de card */
.period-summary-badge {
    font-size: .65rem; font-weight: 700;
    background: #dbeafe; color: #1d4ed8;
    padding: 3px 10px; border-radius: 10px;
    white-space: nowrap;
}

/* États vides */
.empty-state-entities {
    display: flex; align-items: center; gap: 10px;
    padding: 20px 16px; color: #94a3b8;
    font-size: .78rem; font-style: italic;
}
.empty-state-entities i { font-size: 1.4rem; opacity: .5; }

/* Input date en erreur */
.input-error { background: #fff5f5 !important; border-color: #fca5a5 !important; }

/* ═══════════════════════════════════════════════════════════════════════════
   FORMULAIRE
   ═══════════════════════════════════════════════════════════════════════════ */
.form-row   { display:flex;align-items:center;gap:8px;margin-bottom:8px;flex-wrap:nowrap; }
.form-lbl   { font-size:.7rem;font-weight:600;color:#64748b;flex-shrink:0; }
.pro-input  { height:32px;padding:0 10px;font-size:.78rem;color:#1e293b;border:1px solid #d1d5db;border-radius:8px;outline:none;transition:border-color .15s,box-shadow .15s;background:#fff; }
.pro-input:focus { border-color:#3b82f6;box-shadow:0 0 0 3px #bfdbfe40; }
.font-mono  { font-family:monospace; }

/* ═══════════════════════════════════════════════════════════════════════════
   MISSION PICKER
   ═══════════════════════════════════════════════════════════════════════════ */
.mission-picker   { display:flex;align-items:center;gap:8px;height:32px;padding:0 10px;border:1px solid #d1d5db;border-radius:8px;cursor:pointer;font-size:.78rem;color:#94a3b8;background:#fff;transition:border-color .15s;min-width:200px; }
.mission-picker.picked { border-color:#3b82f6;background:#f0f7ff; }
.picker-icon      { color:#94a3b8;font-size:.85rem;flex-shrink:0; }
.picker-chevron   { margin-left:auto;color:#94a3b8;font-size:.85rem;flex-shrink:0; }
.picker-placeholder { font-style:italic;font-size:.75rem; }
.picker-value     { color:#1d4ed8;font-weight:700;font-size:.78rem;font-family:monospace; }
.btn-icon-danger  { display:flex;align-items:center;justify-content:center;width:26px;height:26px;border:1px solid #fca5a5;border-radius:7px;background:#fff5f5;color:#ef4444;cursor:pointer;font-size:.85rem;flex-shrink:0; }
.btn-icon-danger:hover { background:#fee2e2; }

/* ═══════════════════════════════════════════════════════════════════════════
   TABLEAUX GÉNÉRIQUES
   ═══════════════════════════════════════════════════════════════════════════ */
.scroll-zone               { overflow:auto; }
.scroll-zone-sel           { max-height:240px; }
.scroll-zone-aud           { max-height:310px; }
.scroll-zone-budget        { max-height:220px; }
.scroll-zone-aud-budget    { max-height:200px; }
.scroll-zone-breakdown     { max-height:150px; }
.scroll-zone-modal         { max-height:440px; }
.scroll-zone-hierarchy     { max-height:180px; }
.scroll-zone-objective     { max-height:80px; }
.sticky-thead th, .sticky-thead-dark th { position:sticky;top:0;z-index:2; }
.sticky-thead th           { background:#f8faff;padding:5px 10px;font-size:.65rem;font-weight:700;color:#64748b;border-bottom:1px solid #e8edf4;white-space:nowrap; }
.sticky-thead-dark th      { background:#1e293b;color:#e2e8f0;padding:6px 10px;font-size:.68rem;font-weight:700;border-bottom:1px solid #334155;white-space:nowrap; }
.empty-row   { text-align:center;padding:24px;color:#94a3b8;font-size:.75rem;font-style:italic; }
.empty-row i { margin-right:6px;font-size:1.1rem; }

/* Tableau équipe sélectionnée */
.sel-table { width:100%;border-collapse:collapse; }
.sel-table td { padding:5px 10px;font-size:.72rem;border-bottom:1px solid #f1f5f9; }
.sel-table tbody tr.row-active { background:#f0f7ff; }
.sel-table tbody tr.row-warn   { background:#fffbeb; }
.sel-table tbody tr:hover      { background:#f8faff; }

/* Checkbox entité dans le tableau équipe */
.entity-checkbox-wrapper {
    display: flex; align-items: center; justify-content: center;
    width: 22px; height: 22px;
    border-radius: 6px;
    border: 1.5px solid #d1d5db;
    background: #f8faff;
    cursor: pointer;
    transition: background .15s, border-color .15s;
    margin: 0 auto;
}
.entity-checkbox-wrapper.indisponible { background: #fef3c7; border-color: #f59e0b; }
.entity-checkbox { width: 14px; height: 14px; cursor: pointer; accent-color: #3b82f6; }

/* ═══════════════════════════════════════════════════════════════════════════
   AUDITEURS GRID
   ═══════════════════════════════════════════════════════════════════════════ */
.aud-grid-header { display:grid;padding:5px 10px;background:#f1f5f9;border-bottom:1px solid #e2e8f0; }
.aud-grid-header div { font-size:.62rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em; }
.aud-row       { display:grid;padding:6px 10px;border-bottom:1px solid #f1f5f9;cursor:pointer;align-items:center;gap:6px;transition:background .1s; }
.aud-row:hover { background:#f8faff; }
.aud-selected  { background:#eff6ff !important;border-left:3px solid #3b82f6; }
.aud-unavail   { opacity:.75; }
.agc { display:flex;align-items:center; }
.aud-avatar   { width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:800;color:#fff;flex-shrink:0; }
.color-0{background:linear-gradient(135deg,#3b82f6,#6366f1);}
.color-1{background:linear-gradient(135deg,#10b981,#059669);}
.color-2{background:linear-gradient(135deg,#f59e0b,#d97706);}
.color-3{background:linear-gradient(135deg,#ef4444,#dc2626);}
.color-4{background:linear-gradient(135deg,#8b5cf6,#7c3aed);}
.color-5{background:linear-gradient(135deg,#0ea5e9,#0284c7);}
.aud-name  { font-size:.7rem;font-weight:700;color:#1e293b; }
.aud-code  { font-size:.6rem;color:#94a3b8;font-family:monospace; }
.role-select,.parent-select { height:26px;font-size:.7rem;border:1px solid #d1d5db;border-radius:7px;padding:0 6px;outline:none;width:100%; }
.no-parent { font-size:.7rem;color:#94a3b8; }
.comp-count      { font-size:.7rem;font-weight:700;color:#6366f1;background:#ede9fe;padding:1px 7px;border-radius:8px;cursor:pointer; }
.comp-count-zero { color:#94a3b8;background:#f1f5f9; }
.comp-total      { opacity:.5;font-size:.65rem; }
.cal-pill        { display:flex;align-items:center;gap:3px;font-size:.7rem;padding:2px 7px;border-radius:8px;cursor:pointer;white-space:nowrap; }
.cal-ok          { background:#dcfce7;color:#15803d; }
.cal-warn        { background:#fef9c3;color:#b45309; }
.cal-count       { font-weight:700;font-size:.68rem; }
.check-box       { width:20px;height:20px;border-radius:6px;border:1.5px solid #d1d5db;display:flex;align-items:center;justify-content:center;background:#fff;transition:.15s; }
.check-box.checked     { background:#3b82f6;border-color:#2563eb;color:#fff; }
.check-box.check-warn  { background:#f59e0b;border-color:#d97706;color:#fff; }
.lock-icon     { font-size:.85rem;color:#94a3b8;cursor:pointer;transition:color .15s; }
.lock-icon:hover { color:#64748b; }
.empty-aud { text-align:center;padding:24px;color:#94a3b8;font-size:.75rem;font-style:italic; }

/* ═══════════════════════════════════════════════════════════════════════════
   ROLES & HIÉRARCHIE
   ═══════════════════════════════════════════════════════════════════════════ */
.role-chip    { display:inline-block;font-size:.62rem;font-weight:800;padding:1px 7px;border-radius:20px;text-align:center; }
.role-DM      { background:linear-gradient(135deg,#fde68a,#f59e0b);color:#78350f; }
.role-CM      { background:linear-gradient(135deg,#bfdbfe,#93c5fd);color:#1e3a8a; }
.role-AS      { background:linear-gradient(135deg,#d1fae5,#6ee7b7);color:#064e3b; }
.role-AJ      { background:linear-gradient(135deg,#e9d5ff,#c4b5fd);color:#4c1d95; }
.code-chip    { font-size:.65rem;font-weight:700;font-family:monospace;background:#f1f5f9;color:#475569;padding:1px 7px;border-radius:6px;white-space:nowrap; }
.code-chip-lg { font-size:.75rem;font-weight:700;font-family:monospace;background:#dbeafe;color:#1d4ed8;padding:2px 10px;border-radius:7px; }
.name-cell    { display:flex;align-items:center;gap:7px; }
.name-avatar  { width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.68rem;font-weight:800;color:#fff;flex-shrink:0;background:linear-gradient(135deg,#3b82f6,#6366f1); }
.avatar-warn  { background:linear-gradient(135deg,#f59e0b,#d97706); }
.name-text div{ line-height:1.3; }
.sub-text     { font-size:.62rem;color:#94a3b8; }
.sup-text     { font-size:.65rem;font-family:monospace;color:#64748b; }
.warn-icon    { display:flex;align-items:center;justify-content:center;width:22px;height:22px;border-radius:6px;font-size:.85rem; }
.warn-icon-amber { background:#fef9c3;color:#b45309; }
.warn-icon-green { background:#dcfce7;color:#15803d; }

.hierarchy-tree { padding: 4px 0; }
.h-node { display:flex;align-items:center;gap:6px;padding:4px 6px;border-radius:7px;margin-bottom:2px; }
.h-node:hover   { background:#f1f5f9; }
.h-node-dm      { background:#fffbeb;border-left:3px solid #f59e0b; }
.h-node-cm      { padding-left:16px; }
.h-node-as      { padding-left:32px; }
.h-node-aj      { padding-left:48px; }
.h-line         { font-size:.75rem;color:#94a3b8;font-family:monospace;flex-shrink:0; }
.h-name         { font-size:.72rem;font-weight:600;color:#334155; }

/* ═══════════════════════════════════════════════════════════════════════════
   BUDGET
   ═══════════════════════════════════════════════════════════════════════════ */
.budget-table { width:100%;border-collapse:collapse; }
.budget-table td { padding:5px 10px;font-size:.72rem;border-bottom:1px solid #f1f5f9; }
.budget-table tbody tr:hover { background:#f8faff; }
.total-row    { background:#f8faff; }
.total-row td { padding:7px 10px;border-top:1.5px solid #e2e8f0; }
.cat-dot      { width:7px;height:7px;border-radius:50%;flex-shrink:0; }
.currency-input { display:flex;align-items:center;border:1px solid #d1d5db;border-radius:8px;overflow:hidden; }
.currency-input .pro-input { border:none;border-radius:0; }
.currency-unit  { font-size:.68rem;font-weight:700;color:#64748b;padding:0 8px;background:#f1f5f9;white-space:nowrap;line-height:32px; }

/* Onglets budget auditeur */
.scroll-zone-tabs { overflow-x:auto; }
.aud-tabs         { display:flex;gap:4px;padding:8px 12px;border-bottom:1px solid #e8edf4;min-width:max-content; }
.aud-tab          { display:flex;align-items:center;gap:6px;padding:4px 10px;border-radius:8px;border:1px solid #e2e8f0;background:#f8faff;cursor:pointer;font-size:.7rem;font-weight:600;color:#64748b;transition:.15s;white-space:nowrap; }
.aud-tab:hover    { background:#f1f5f9; }
.aud-tab-active   { background:#eff6ff;border-color:#93c5fd;color:#1d4ed8; }
.tab-avatar       { width:20px;height:20px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.6rem;font-weight:800;color:#fff; }
.tab-name         { font-family:monospace;font-size:.72rem; }
.tab-amount       { font-size:.65rem;color:#10b981;font-weight:700;margin-left:2px; }
.aud-budget-panel { }
.aud-budget-header { display:flex;align-items:center;gap:10px;padding:8px 12px;border-bottom:1px solid #f1f5f9;background:#fafbfc; }
.budget-total-display { background:linear-gradient(135deg,#eff6ff,#e0e7ff);border-radius:10px;padding:12px 16px;text-align:center; }
.btd-label { display:block;font-size:.65rem;color:#64748b;margin-bottom:2px; }
.btd-value { font-size:1.1rem;font-weight:800;color:#1d4ed8; }
.btd-value small { font-size:.6em;color:#64748b;font-weight:500;margin-left:4px; }
.budget-breakdown { }
.bb-row    { display:flex;align-items:center;gap:8px;padding:3px 0;font-size:.72rem; }
.bb-dot    { width:8px;height:8px;border-radius:50%;flex-shrink:0; }
.bb-label  { flex:1;color:#475569; }
.bb-val    { font-weight:700;color:#1e293b;font-family:monospace;font-size:.7rem; }
.budget-bars { }
.bbar      { height:8px;border-radius:10px;background:#f1f5f9;display:flex;overflow:hidden; }
.bbar-seg  { height:100%;transition:width .5s ease; }
.bbar-legend { display:flex;gap:10px;margin-top:4px; }
.bbar-legend span { display:flex;align-items:center;gap:4px;font-size:.62rem;color:#64748b; }
.bbar-legend i { width:8px;height:8px;border-radius:50%;display:inline-block; }

/* ═══════════════════════════════════════════════════════════════════════════
   MODALES
   ═══════════════════════════════════════════════════════════════════════════ */
.pro-modal-overlay { position:fixed;inset:0;background:rgba(15,23,42,.55);backdrop-filter:blur(4px);z-index:1050;display:flex;align-items:center;justify-content:center;padding:20px; }
.pro-modal         { background:#fff;border-radius:16px;width:100%;max-width:900px;max-height:90vh;display:flex;flex-direction:column;box-shadow:0 25px 60px rgba(15,23,42,.3);overflow:hidden; }
.pro-modal-header  { display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid #e8edf4;background:#f8faff;flex-shrink:0; }
.pro-modal-header h5   { font-size:.88rem;font-weight:800;color:#1e293b;margin:0; }
.pro-modal-header small { font-size:.7rem;color:#94a3b8; }
.modal-icon-wrap  { width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#3b82f6,#6366f1);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1rem;flex-shrink:0; }
.pro-modal-close  { width:30px;height:30px;border-radius:8px;border:1px solid #e2e8f0;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:.9rem;color:#94a3b8;background:#fff;transition:.15s; }
.pro-modal-close:hover { background:#fee2e2;color:#ef4444;border-color:#fca5a5; }
.pro-modal-filters { display:flex;align-items:center;gap:8px;padding:10px 16px;background:#f8faff;border-bottom:1px solid #e8edf4;flex-shrink:0;flex-wrap:wrap; }
.pro-modal-body    { flex:1;overflow:auto;padding:12px 16px; }
.pro-modal-footer  { display:flex;align-items:center;justify-content:space-between;padding:10px 16px;border-top:1px solid #e8edf4;background:#f8faff;flex-shrink:0; }
.modal-result-count { font-size:.7rem;color:#94a3b8;margin-bottom:8px; }
.modal-table          { width:100%;border-collapse:collapse; }
.modal-table td       { padding:6px 10px;font-size:.72rem;border-bottom:1px solid #f1f5f9; }
.modal-table tbody tr:hover          { background:#f8faff;cursor:pointer; }
.modal-table tbody tr.modal-row-active { background:#eff6ff; }

/* ═══════════════════════════════════════════════════════════════════════════
   INDISPONIBILITÉS / COMPÉTENCES
   ═══════════════════════════════════════════════════════════════════════════ */
.unavail-list   { display:flex;flex-direction:column;gap:8px; }
.unavail-item   { border-left:3px solid;border-radius:0 8px 8px 0;padding:8px 12px;background:#fafafa; }
.unavail-header { display:flex;align-items:center;gap:8px;margin-bottom:3px; }
.unavail-type   { font-size:.68rem;font-weight:700;padding:2px 8px;border-radius:6px;display:inline-flex;align-items:center; }
.unavail-global-badge { font-size:.58rem;background:#fff;padding:1px 5px;border-radius:4px;margin-left:4px;opacity:.8; }
.unavail-duration { font-size:.68rem;color:#94a3b8;margin-left:auto; }
.unavail-dates  { font-size:.7rem;color:#475569;display:flex;align-items:center;gap:4px; }
.unavail-reason { font-size:.68rem;color:#64748b;font-style:italic;margin-top:2px;display:flex;align-items:center;gap:4px; }

.comp-list      { display:flex;flex-direction:column;gap:8px; }
.comp-item      { border:1px solid #e2e8f0;border-radius:8px;padding:8px 12px;background:#fafafa; }
.comp-header    { display:flex;align-items:center;gap:8px;margin-bottom:4px; }
.comp-code      { font-size:.65rem;font-weight:700;font-family:monospace;background:#f1f5f9;color:#475569;padding:1px 7px;border-radius:5px; }
.comp-name      { font-size:.72rem;font-weight:600;color:#334155;flex:1; }
.comp-level-req { font-size:.65rem;color:#94a3b8; }
.comp-status    { display:inline-flex;align-items:center;gap:4px;font-size:.7rem;font-weight:600;padding:2px 8px;border-radius:6px; }
.comp-status.success { background:#dcfce7;color:#15803d; }
.comp-status.danger  { background:#fee2e2;color:#dc2626; }

/* ═══════════════════════════════════════════════════════════════════════════
   MISSION INFO
   ═══════════════════════════════════════════════════════════════════════════ */
.mission-info-grid { display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:8px;margin-bottom:8px; }
.mi-item  { display:flex;flex-direction:column;gap:2px; }
.mi-label { font-size:.62rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.04em; }
.mi-value { font-size:.75rem;color:#334155;font-weight:600; }
.priority-badge { display:inline-block;font-size:.62rem;font-weight:800;padding:1px 8px;border-radius:10px; }
.prio-critique { background:#fee2e2;color:#b91c1c; }
.prio-haute    { background:#fef9c3;color:#b45309; }
.prio-moyenne  { background:#dbeafe;color:#1d4ed8; }
.prio-basse    { background:#f1f5f9;color:#64748b; }
.status-chip   { font-size:.62rem;font-weight:700;background:#f1f5f9;color:#475569;padding:1px 8px;border-radius:8px;text-transform:capitalize; }
.objective-box { font-size:.72rem;color:#475569;background:#f8faff;border-radius:8px;padding:8px 10px;line-height:1.5;min-height:40px; }

/* ═══════════════════════════════════════════════════════════════════════════
   BOUTONS & SEARCH
   ═══════════════════════════════════════════════════════════════════════════ */
.pro-btn        { display:inline-flex;align-items:center;height:34px;padding:0 16px;border-radius:9px;font-size:.78rem;font-weight:700;cursor:pointer;transition:.15s;border:1px solid transparent; }
.pro-btn-sm     { height:28px;padding:0 12px;font-size:.72rem; }
.pro-btn-primary { background:linear-gradient(135deg,#3b82f6,#6366f1);color:#fff;box-shadow:0 2px 8px #3b82f640; }
.pro-btn-primary:hover { transform:translateY(-1px);box-shadow:0 4px 14px #3b82f660; }
.pro-btn-outline { background:#fff;border:1.5px solid #e2e8f0;color:#64748b; }
.pro-btn-outline:hover { border-color:#93c5fd;color:#1d4ed8; }
.pro-btn-ghost  { background:transparent;border:1px solid transparent;color:#64748b; }
.pro-btn-ghost:hover { background:#f1f5f9; }
.pro-btn-disabled { opacity:.5;cursor:not-allowed;transform:none !important; }

.search-box-sm  { display:flex;align-items:center;gap:6px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:8px;padding:0 8px;height:28px; }
.search-box-sm i { color:#94a3b8;font-size:.85rem; }
.search-box-sm input { border:none;background:transparent;outline:none;font-size:.72rem;color:#1e293b;width:140px; }
.search-box-lg  { display:flex;align-items:center;gap:8px;background:#f8faff;border:1px solid #e2e8f0;border-radius:9px;padding:0 12px;height:32px;flex:1; }
.search-box-lg i { color:#94a3b8;font-size:.9rem; }
.search-box-lg input { border:none;background:transparent;outline:none;font-size:.78rem;color:#1e293b;width:100%; }

/* ═══════════════════════════════════════════════════════════════════════════
   LÉGENDE & ALERTES
   ═══════════════════════════════════════════════════════════════════════════ */
.legend-bar  { display:flex;gap:12px;padding:5px 12px;border-bottom:1px solid #f1f5f9;background:#fafbfc; }
.leg-item    { display:flex;align-items:center;gap:4px;font-size:.65rem;color:#64748b; }
.alert-pro   { display:flex;align-items:flex-start;gap:10px;padding:10px 16px;border-radius:10px;font-size:.78rem; }
.alert-pro-success { background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d; }
.alert-pro-danger  { background:#fff5f5;border:1px solid #fecaca;color:#b91c1c; }
.alert-pro-warning { background:#fffbeb;border:1px solid #fde68a;color:#b45309; }

/* ═══════════════════════════════════════════════════════════════════════════
   TRANSITIONS
   ═══════════════════════════════════════════════════════════════════════════ */
.slide-down-enter-active,.slide-down-leave-active { transition:all .3s; }
.slide-down-enter-from,.slide-down-leave-to { opacity:0;transform:translateY(-10px); }
.mfade-enter-active,.mfade-leave-active { transition:all .25s; }
.mfade-enter-from,.mfade-leave-to { opacity:0;transform:scale(.97); }
</style>