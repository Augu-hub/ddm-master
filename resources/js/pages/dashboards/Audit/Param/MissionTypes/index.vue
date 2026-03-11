<template>
  <VerticalLayout>

    <!-- EN-TÊTE -->
    <div class="page-header mb-4">
      <div class="page-header__top">
        <div>
          <div class="d-flex align-items-center gap-2 mb-2">
            <span class="badge-module">AUDIT.CORE</span>
            <i class="ti ti-chevron-right bc-sep"></i>
            <span class="badge-module" style="background:#374151">PARAM</span>
            <i class="ti ti-chevron-right bc-sep"></i>
            <span class="badge-module" style="background:#2563EB">TYPES DE MISSION</span>
            <span class="status-chip"><span class="dot"></span>DDMParam</span>
          </div>
          <h2 class="page-title">Types de Mission d'Audit</h2>
          <p class="page-desc">{{ stats.total_types }} types · phases N1+N2 · affectation des types d'audit</p>
        </div>
        <button class="btn btn--primary" @click="openAddType">
          <i class="ti ti-plus"></i> Nouveau type
        </button>
      </div>
      <div class="stats-row">
        <div class="stat-chip"><i class="ti ti-clipboard-list" style="color:#2563EB"></i><span class="stat-chip__val">{{ stats.total_types }}</span><span class="stat-chip__lbl">types</span></div>
        <div class="stat-chip"><i class="ti ti-circle-check" style="color:#059669"></i><span class="stat-chip__val">{{ stats.active_types }}</span><span class="stat-chip__lbl">actifs</span></div>
        <div class="stat-chip"><i class="ti ti-layers" style="color:#D97706"></i><span class="stat-chip__val">{{ stats.total_phases }}</span><span class="stat-chip__lbl">phases</span></div>
        <div class="stat-chip"><i class="ti ti-file-description" style="color:#7C3AED"></i><span class="stat-chip__val">{{ stats.total_forms }}</span><span class="stat-chip__lbl">fiches</span></div>
      </div>
    </div>

    <!-- TYPES -->
    <div class="types-list">
      <div v-for="t in missionTypes" :key="t.id"
           class="type-block" :style="{'--tc': t.color||'#2563EB'}"
           :class="{'type-block--open': expanded.has(t.id)}">

        <!-- LIGNE TYPE -->
        <div class="type-row" @click="toggle(t.id)">
          <div class="type-row-l">
            <span class="chevron" :class="{'chevron--open': expanded.has(t.id)}">
              <i class="ti ti-chevron-right"></i>
            </span>
            <div class="type-logo" :style="{background:(t.color||'#2563EB')+'18'}">
              <img v-if="t.logo_path" :src="'/storage/'+t.logo_path" :alt="t.code" class="logo-img"/>
              <i v-else :class="t.icon||'ti ti-clipboard-list'" :style="{color:t.color||'#2563EB'}"></i>
            </div>
            <div class="type-info flex-1 min0">
              <div class="d-flex align-items-center gap-2 mb1 flex-wrap">
                <span class="badge-code" :style="{background:t.color||'#2563EB'}">{{ t.code }}</span>
                <span v-if="!t.is_active" class="badge-off">Inactif</span>
                <!-- Audit types pills -->
                <span v-for="at in (t.audit_types||[])" :key="at.id"
                      class="at-pill" :style="{background:at.color+'18',color:at.color,borderColor:at.color+'55'}">
                  <i :class="at.icon" style="font-size:.55rem"></i>{{ at.code }}
                </span>
                <button class="ibtn-star" title="Affecter types d'audit" @click.stop="openSyncAT('type',t,t)">
                  <i class="ti ti-tag-starred"></i>
                </button>
              </div>
              <span class="type-label">{{ t.label }}</span>
              <span class="type-meta">{{ t.phases?.length??0 }} phases · {{ t.forms_count??0 }} fiches</span>
            </div>
          </div>
          <div class="type-row-r" @click.stop>
            <button class="ibtn ibtn--add"  @click="openAddPhase(t)"><i class="ti ti-plus"></i></button>
            <button class="ibtn ibtn--logo" @click="openLogo(t)"><i class="ti ti-photo"></i></button>
            <button class="ibtn ibtn--edit" @click="openEditType(t)"><i class="ti ti-pencil"></i></button>
            <button class="ibtn ibtn--del"  @click="openDelType(t)"><i class="ti ti-trash"></i></button>
          </div>
        </div>

        <!-- PHASES -->
        <div v-if="expanded.has(t.id)" class="phases-panel">
          <div v-if="!t.phases?.length" class="phases-empty">
            <i class="ti ti-file-off"></i> Aucune phase —
            <button class="link-btn" @click="openAddPhase(t)">Ajouter</button>
          </div>

          <div v-for="ph in t.phases" :key="ph.id" class="phase-n1">
            <div class="phase-row" @click="togglePh(ph.id)">
              <div class="phase-row-l">
                <span class="chev-sm" :class="{'chev-sm--open': expandedPh.has(ph.id)}">
                  <i v-if="ph.sub_forms?.length" class="ti ti-chevron-right"></i>
                  <i v-else class="ti ti-minus" style="color:#e5e7eb"></i>
                </span>
                <i :class="ph.icon||'ti ti-file-description'" class="ph-ico" :style="{color:t.color||'#6b7280'}"></i>
                <div class="ph-info flex-1 min0">
                  <div class="d-flex align-items-center gap-1 flex-wrap mb1">
                    <span class="ph-label">{{ ph.label }}</span>
                    <span v-for="at in (ph.audit_types||[])" :key="at.id"
                          class="at-pill at-pill--sm" :style="{background:at.color+'18',color:at.color,borderColor:at.color+'55'}">
                      {{ at.code }}
                    </span>
                    <button class="ibtn-star ibtn-star--sm" @click.stop="openSyncAT('form',t,ph)">
                      <i class="ti ti-tag-starred"></i>
                    </button>
                  </div>
                  <span class="ph-url">{{ ph.url_path }}</span>
                </div>
                <span v-if="ph.sub_forms?.length" class="sub-badge">{{ ph.sub_forms.length }}</span>
              </div>
              <div class="phase-row-r" @click.stop>
                <button class="ibtn ibtn--sm ibtn--add"  @click="openAddSub(t,ph)"><i class="ti ti-plus"></i></button>
                <button class="ibtn ibtn--sm ibtn--edit" @click="openEditPhase(t,ph)"><i class="ti ti-pencil"></i></button>
                <button class="ibtn ibtn--sm ibtn--del"  @click="openDelPhase(t,ph)"><i class="ti ti-trash"></i></button>
              </div>
            </div>

            <!-- SOUS-PHASES N2 -->
            <div v-if="expandedPh.has(ph.id) && ph.sub_forms?.length" class="subphases">
              <div v-for="sub in ph.sub_forms" :key="sub.id" class="sub-row">
                <div class="tree-l"></div>
                <i class="ti ti-file-description sub-ico"></i>
                <div class="ph-info flex-1 min0">
                  <div class="d-flex align-items-center gap-1 flex-wrap mb1">
                    <span class="ph-label">{{ sub.label }}</span>
                    <span v-for="at in (sub.audit_types||[])" :key="at.id"
                          class="at-pill at-pill--sm" :style="{background:at.color+'18',color:at.color,borderColor:at.color+'55'}">
                      {{ at.code }}
                    </span>
                    <button class="ibtn-star ibtn-star--sm" @click.stop="openSyncAT('form',t,sub)">
                      <i class="ti ti-tag-starred"></i>
                    </button>
                  </div>
                  <span class="ph-url">{{ sub.url_path }}</span>
                </div>
                <div class="d-flex gap-1" @click.stop>
                  <button class="ibtn ibtn--sm ibtn--edit" @click="openEditPhase(t,sub)"><i class="ti ti-pencil"></i></button>
                  <button class="ibtn ibtn--sm ibtn--del"  @click="openDelPhase(t,sub)"><i class="ti ti-trash"></i></button>
                </div>
              </div>
            </div>
          </div>

          <button class="add-phase-btn" @click="openAddPhase(t)">
            <i class="ti ti-plus"></i> Ajouter une phase à <b>{{ t.label }}</b>
          </button>
        </div>
      </div>
    </div>

    <!-- FLASH -->
    <Transition name="flash-t">
      <div v-if="flash" class="flash" :class="'flash--'+flash.type">
        <i :class="flash.type==='success'?'ti ti-circle-check':'ti ti-circle-x'"></i>{{ flash.msg }}
      </div>
    </Transition>

    <!-- ═══════════════════════════════════════════════════════
         MODAL : AFFECTER TYPES D'AUDIT — AVEC VUE CHAIRS
    ═══════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <div v-if="showAT" class="overlay" @click.self="showAT=false">
        <div class="mbox mbox--md">
          <div class="mbox__h">
            <div class="d-flex align-items-center gap-2">
              <span class="mico" style="background:#f5f3ff;color:#7C3AED"><i class="ti ti-tag-starred"></i></span>
              <div>
                <h4 class="mbox__title">Types d'audit applicables</h4>
                <p class="mbox__sub" v-if="atTarget">
                  {{ atMode==='type' ? 'Type de mission' : 'Phase' }} :
                  <b>{{ atMode==='type' ? atTarget.code : atTarget.label?.slice(0,30) }}</b>
                </p>
              </div>
            </div>
            <button class="mcls" @click="showAT=false"><i class="ti ti-x"></i></button>
          </div>

          <div class="mbox__b">
            <!-- INFO -->
            <div class="info-bar mb3">
              <i class="ti ti-info-circle"></i>
              Glissez les types d'audit de gauche à droite ou utilisez les flèches.
              La colonne droite = types <b>applicables</b> à cet élément.
            </div>

            <!-- CHAIRS LAYOUT -->
            <div class="chairs-wrap">
              <!-- COLONNE GAUCHE : disponibles -->
              <div class="chairs-col">
                <div class="chairs-col__head">
                  <i class="ti ti-list" style="color:#6b7280"></i>
                  Disponibles
                  <span class="chairs-count">{{ atLeft.length }}</span>
                </div>
                <div class="chairs-list"
                     @dragover.prevent
                     @drop="onDrop('left', $event)">
                  <div v-if="!atLeft.length" class="chairs-empty">
                    <i class="ti ti-check-circle" style="color:#059669"></i> Tous affectés
                  </div>
                  <div v-for="at in atLeft" :key="at.id"
                       class="chairs-item"
                       :class="{'chairs-item--selected': leftSel.has(at.id)}"
                       :style="leftSel.has(at.id)?{borderColor:at.color,background:at.color+'10'}:{}"
                       draggable="true"
                       @dragstart="onDragStart('left', at, $event)"
                       @click="toggleLeftSel(at.id)">
                    <div class="chairs-item__ico" :style="{background:at.color+'18',color:at.color}">
                      <i :class="at.icon"></i>
                    </div>
                    <div class="chairs-item__info">
                      <span class="chairs-item__code" :style="{color:at.color}">{{ at.code }}</span>
                      <span class="chairs-item__lbl">{{ at.label }}</span>
                    </div>
                    <i v-if="leftSel.has(at.id)" class="ti ti-check chairs-item__check" :style="{color:at.color}"></i>
                  </div>
                </div>
              </div>

              <!-- BOUTONS TRANSFERT -->
              <div class="chairs-btns">
                <button class="transfer-btn transfer-btn--right" title="Déplacer à droite"
                        :disabled="!leftSel.size" @click="moveToRight">
                  <i class="ti ti-chevrons-right"></i>
                </button>
                <button class="transfer-btn transfer-btn--all-right" title="Tout à droite"
                        :disabled="!atLeft.length" @click="moveAllRight">
                  <i class="ti ti-chevron-right"></i><i class="ti ti-chevron-right" style="margin-left:-5px"></i>
                </button>
                <button class="transfer-btn transfer-btn--all-left" title="Tout à gauche"
                        :disabled="!atRight.length" @click="moveAllLeft">
                  <i class="ti ti-chevron-left" style="margin-right:-5px"></i><i class="ti ti-chevron-left"></i>
                </button>
                <button class="transfer-btn transfer-btn--left" title="Déplacer à gauche"
                        :disabled="!rightSel.size" @click="moveToLeft">
                  <i class="ti ti-chevrons-left"></i>
                </button>
              </div>

              <!-- COLONNE DROITE : sélectionnés -->
              <div class="chairs-col chairs-col--right">
                <div class="chairs-col__head">
                  <i class="ti ti-check-circle" style="color:#059669"></i>
                  Applicables
                  <span class="chairs-count chairs-count--green">{{ atRight.length }}</span>
                  <span v-if="!atRight.length" class="chairs-hint">= tous les types</span>
                </div>
                <div class="chairs-list"
                     @dragover.prevent
                     @drop="onDrop('right', $event)">
                  <div v-if="!atRight.length" class="chairs-empty">
                    <i class="ti ti-infinity" style="color:#9ca3af"></i> Applicable à tous
                  </div>
                  <div v-for="at in atRight" :key="at.id"
                       class="chairs-item chairs-item--active"
                       :class="{'chairs-item--selected': rightSel.has(at.id)}"
                       :style="{borderColor:at.color+'55',background:rightSel.has(at.id)?at.color+'15':at.color+'08'}"
                       draggable="true"
                       @dragstart="onDragStart('right', at, $event)"
                       @click="toggleRightSel(at.id)">
                    <div class="chairs-item__ico" :style="{background:at.color+'22',color:at.color}">
                      <i :class="at.icon"></i>
                    </div>
                    <div class="chairs-item__info">
                      <span class="chairs-item__code" :style="{color:at.color}">{{ at.code }}</span>
                      <span class="chairs-item__lbl">{{ at.label }}</span>
                    </div>
                    <i v-if="rightSel.has(at.id)" class="ti ti-check chairs-item__check" :style="{color:at.color}"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="mbox__f">
            <button class="btn btn--ghost btn--sm" @click="moveAllLeft">
              <i class="ti ti-refresh"></i> Tout désélect.
            </button>
            <button class="btn btn--ghost" @click="showAT=false">Annuler</button>
            <button class="btn btn--primary" :disabled="saving" @click="saveSyncAT">
              <span v-if="saving" class="spin"></span>
              <i v-else class="ti ti-check"></i>
              {{ saving ? 'Enregistrement…' : 'Enregistrer' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ═══ MODAL TYPE DE MISSION (créer/modifier) ═══ -->
    <Teleport to="body">
      <div v-if="showType" class="overlay" @click.self="showType=false">
        <div class="mbox">
          <div class="mbox__h">
            <div class="d-flex align-items-center gap-2">
              <span class="mico"><i :class="editType?'ti ti-pencil':'ti ti-plus'"></i></span>
              <div>
                <h4 class="mbox__title">{{ editType?'Modifier le type':'Nouveau type de mission' }}</h4>
                <p v-if="editType" class="mbox__sub">{{ editType.code }}</p>
              </div>
            </div>
            <button class="mcls" @click="showType=false"><i class="ti ti-x"></i></button>
          </div>
          <div class="mbox__b">
            <div class="fg-grid">
              <div class="fg">
                <label class="flbl">Code <span class="req">*</span></label>
                <input v-model="tf.code" class="fi" :disabled="!!editType" placeholder="PREPARATION" style="text-transform:uppercase"/>
              </div>
              <div class="fg fg2">
                <label class="flbl">Libellé <span class="req">*</span></label>
                <input v-model="tf.label" class="fi" placeholder="Préparation"/>
              </div>
              <div class="fg">
                <label class="flbl">Couleur</label>
                <div class="d-flex gap-2">
                  <input v-model="tf.color" type="color" class="fi-color"/>
                  <input v-model="tf.color" class="fi fi-mono"/>
                </div>
              </div>
              <div class="fg fg2">
                <label class="flbl">Icône</label>
                <div class="d-flex gap-2 align-items-center">
                  <i :class="tf.icon||'ti ti-clipboard-list'" :style="{color:tf.color,fontSize:'1.05rem'}" class="flex-shrink-0"></i>
                  <input v-model="tf.icon" class="fi" placeholder="ti ti-search"/>
                </div>
              </div>
              <div class="fg fg3">
                <label class="flbl">Description</label>
                <textarea v-model="tf.description" class="fi fi-ta" rows="2"></textarea>
              </div>
              <div class="fg">
                <label class="flbl">Ordre</label>
                <input v-model.number="tf.sort_order" type="number" class="fi"/>
              </div>
              <div v-if="editType" class="fg d-flex align-items-end pb1">
                <label class="fchk"><input type="checkbox" v-model="tf.is_active"/><span>Actif</span></label>
              </div>
            </div>
          </div>
          <div class="mbox__f">
            <button class="btn btn--ghost" @click="showType=false">Annuler</button>
            <button class="btn btn--primary" :disabled="saving" @click="saveType">
              <span v-if="saving" class="spin"></span>
              <i v-else :class="editType?'ti ti-device-floppy':'ti ti-plus'"></i>
              {{ saving?'Enregistrement…':(editType?'Enregistrer':'Créer') }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ═══ MODAL PHASE / SOUS-PHASE ═══ -->
    <Teleport to="body">
      <div v-if="showPhase" class="overlay" @click.self="showPhase=false">
        <div class="mbox mbox--md">
          <div class="mbox__h">
            <div class="d-flex align-items-center gap-2">
              <span class="mico"><i :class="editPhase?'ti ti-pencil':'ti ti-plus'"></i></span>
              <div>
                <h4 class="mbox__title">
                  {{ editPhase?'Modifier':(phParent?'Sous-phase':'Phase') }}
                  <span v-if="curType" class="badge-code ms2" :style="{background:curType.color||'#2563EB'}">{{ curType.code }}</span>
                </h4>
                <p class="mbox__sub">
                  {{ slugPreview(pf.label) ? '/m/audit.core/missions/'+curType?.code?.toLowerCase()+'/'+slugPreview(pf.label) : 'URL générée automatiquement' }}
                </p>
              </div>
            </div>
            <button class="mcls" @click="showPhase=false"><i class="ti ti-x"></i></button>
          </div>

          <div class="mbox__b">
            <div class="fg mb3">
              <label class="flbl">Libellé <span class="req">*</span></label>
              <input v-model="pf.label" class="fi" autofocus placeholder="ex: Programmes de travail"/>
            </div>
            <div class="fg mb3">
              <label class="flbl">Icône</label>
              <div class="d-flex gap-2 align-items-center">
                <i :class="pf.icon||'ti ti-file-description'" :style="{color:curType?.color,fontSize:'1.05rem'}" class="flex-shrink-0"></i>
                <input v-model="pf.icon" class="fi" placeholder="ti ti-file-description"/>
              </div>
            </div>
            <div class="fg mb3">
              <label class="flbl">Description</label>
              <textarea v-model="pf.description" class="fi fi-ta" rows="2"></textarea>
            </div>

            <!-- CHAIRS TYPES D'AUDIT pour la phase -->
            <div class="fg">
              <label class="flbl mb1">Types d'audit applicables à cette phase</label>
              <div class="info-bar info-bar--sm mb2">
                <i class="ti ti-info-circle"></i>
                Déplacez les types d'audit vers la droite pour les rendre applicables.
                Colonne droite vide = applicable à <b>tous</b>.
              </div>
              <div class="chairs-wrap chairs-wrap--compact">
                <!-- Gauche -->
                <div class="chairs-col">
                  <div class="chairs-col__head">
                    <i class="ti ti-list" style="color:#6b7280;font-size:.75rem"></i>
                    Disponibles <span class="chairs-count">{{ phLeft.length }}</span>
                  </div>
                  <div class="chairs-list chairs-list--compact" @dragover.prevent @drop="onDropPh('left',$event)">
                    <div v-if="!phLeft.length" class="chairs-empty chairs-empty--sm">
                      <i class="ti ti-check-circle" style="color:#059669"></i> Tous affectés
                    </div>
                    <div v-for="at in phLeft" :key="at.id"
                         class="chairs-item chairs-item--compact"
                         :class="{'chairs-item--selected': phLeftSel.has(at.id)}"
                         :style="phLeftSel.has(at.id)?{borderColor:at.color,background:at.color+'10'}:{}"
                         draggable="true"
                         @dragstart="onDragStartPh('left',at,$event)"
                         @click="togglePhLeftSel(at.id)">
                      <i :class="at.icon" :style="{color:at.color,fontSize:'.75rem'}" class="flex-shrink-0"></i>
                      <span class="chairs-item__code" :style="{color:at.color,fontSize:'.62rem'}">{{ at.code }}</span>
                      <span class="chairs-item__lbl" style="font-size:.7rem">{{ at.short_label||at.label }}</span>
                    </div>
                  </div>
                </div>

                <!-- Boutons -->
                <div class="chairs-btns chairs-btns--compact">
                  <button class="transfer-btn" :disabled="!phLeftSel.size"  @click="phMoveToRight"><i class="ti ti-chevrons-right"></i></button>
                  <button class="transfer-btn" :disabled="!phLeft.length"   @click="phMoveAllRight"><i class="ti ti-chevron-right"></i></button>
                  <button class="transfer-btn" :disabled="!phRight.length"  @click="phMoveAllLeft"><i class="ti ti-chevron-left"></i></button>
                  <button class="transfer-btn" :disabled="!phRightSel.size" @click="phMoveToLeft"><i class="ti ti-chevrons-left"></i></button>
                </div>

                <!-- Droite -->
                <div class="chairs-col chairs-col--right">
                  <div class="chairs-col__head">
                    <i class="ti ti-check-circle" style="color:#059669;font-size:.75rem"></i>
                    Applicables <span class="chairs-count chairs-count--green">{{ phRight.length }}</span>
                    <span v-if="!phRight.length" class="chairs-hint">= tous</span>
                  </div>
                  <div class="chairs-list chairs-list--compact" @dragover.prevent @drop="onDropPh('right',$event)">
                    <div v-if="!phRight.length" class="chairs-empty chairs-empty--sm">
                      <i class="ti ti-infinity" style="color:#9ca3af"></i> Tous les types
                    </div>
                    <div v-for="at in phRight" :key="at.id"
                         class="chairs-item chairs-item--compact chairs-item--active"
                         :class="{'chairs-item--selected': phRightSel.has(at.id)}"
                         :style="{borderColor:at.color+'55',background:phRightSel.has(at.id)?at.color+'15':at.color+'08'}"
                         draggable="true"
                         @dragstart="onDragStartPh('right',at,$event)"
                         @click="togglePhRightSel(at.id)">
                      <i :class="at.icon" :style="{color:at.color,fontSize:'.75rem'}" class="flex-shrink-0"></i>
                      <span class="chairs-item__code" :style="{color:at.color,fontSize:'.62rem'}">{{ at.code }}</span>
                      <span class="chairs-item__lbl" style="font-size:.7rem">{{ at.short_label||at.label }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="mbox__f">
            <button class="btn btn--ghost" @click="showPhase=false">Annuler</button>
            <button class="btn btn--primary" :disabled="saving" @click="savePhase">
              <span v-if="saving" class="spin"></span>
              <i v-else class="ti ti-check"></i>
              {{ saving?'Enregistrement…':'Enregistrer' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ═══ MODAL LOGO ═══ -->
    <Teleport to="body">
      <div v-if="showLogoM" class="overlay" @click.self="showLogoM=false">
        <div class="mbox mbox--sm">
          <div class="mbox__h">
            <div class="d-flex align-items-center gap-2">
              <span class="mico"><i class="ti ti-photo"></i></span>
              <h4 class="mbox__title">Logo
                <span v-if="curType" class="badge-code ms2" :style="{background:curType.color}">{{ curType.code }}</span>
              </h4>
            </div>
            <button class="mcls" @click="showLogoM=false"><i class="ti ti-x"></i></button>
          </div>
          <div class="mbox__b">
            <div class="file-drop" @click="logoRef?.click()">
              <i class="ti ti-cloud-upload"></i>
              <p>{{ logoFile ? logoFile.name : 'Cliquer pour choisir' }}</p>
              <span>PNG / JPG · max 2 Mo</span>
              <input ref="logoRef" type="file" accept="image/*" style="display:none"
                     @change="(e:any) => logoFile = e.target.files?.[0] ?? null"/>
            </div>
          </div>
          <div class="mbox__f">
            <button class="btn btn--ghost" @click="showLogoM=false">Annuler</button>
            <button class="btn btn--primary" :disabled="!logoFile||saving" @click="uploadLogo">
              <span v-if="saving" class="spin"></span>
              <i v-else class="ti ti-upload"></i>
              {{ saving?'Upload…':'Enregistrer' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ═══ MODAL SUPPRIMER ═══ -->
    <Teleport to="body">
      <div v-if="showDel" class="overlay" @click.self="showDel=false">
        <div class="mbox mbox--sm">
          <div class="mbox__h mbox__h--danger">
            <div class="d-flex align-items-center gap-2">
              <span class="mico mico--danger"><i class="ti ti-alert-triangle"></i></span>
              <h4 class="mbox__title">Supprimer</h4>
            </div>
            <button class="mcls" @click="showDel=false"><i class="ti ti-x"></i></button>
          </div>
          <div class="mbox__b">
            <div class="alert-warn">
              <i class="ti ti-info-circle"></i>
              <span v-if="delMode==='type'">Supprime le type <b>{{ delItem?.code }}</b> et toutes ses phases.</span>
              <span v-else>Supprime la phase <b>{{ delItem?.label }}</b>
                <template v-if="delItem?.sub_forms?.length"> et ses {{ delItem.sub_forms.length }} sous-phases.</template>
              </span>
            </div>
          </div>
          <div class="mbox__f">
            <button class="btn btn--ghost" @click="showDel=false">Annuler</button>
            <button class="btn btn--danger" :disabled="deleting" @click="confirmDel">
              <span v-if="deleting" class="spin spin--r"></span>
              <i v-else class="ti ti-trash"></i>
              {{ deleting?'Suppression…':'Confirmer' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

  </VerticalLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import VerticalLayout from '@/layouts/VerticalLayout.vue'

// ── Props sécurisés ────────────────────────────────────────────
const props = withDefaults(defineProps<{
  missionTypes?:  any[]
  allAuditTypes?: any[]
  stats?: { total_types:number; active_types:number; total_phases:number; total_forms:number }
}>(), {
  missionTypes:  () => [],
  allAuditTypes: () => [],
  stats: () => ({ total_types:0, active_types:0, total_phases:0, total_forms:0 }),
})

const allAT = computed(() => props.allAuditTypes ?? [])

// Accordion
const expanded   = ref<Set<number>>(new Set())
const expandedPh = ref<Set<number>>(new Set())
const toggle    = (id:number) => expanded.value.has(id)   ? expanded.value.delete(id)   : expanded.value.add(id)
const togglePh  = (id:number) => expandedPh.value.has(id) ? expandedPh.value.delete(id) : expandedPh.value.add(id)

// Utils
const saving   = ref(false)
const deleting = ref(false)
const flash    = ref<{type:'success'|'error';msg:string}|null>(null)
const logoFile = ref<File|null>(null)
const logoRef  = ref<HTMLInputElement|null>(null)

function csrf() { return (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content ?? '' }
async function api(method:string, url:string, body?:any) {
  const r = await fetch(url, {
    method, headers: {'Content-Type':'application/json','X-CSRF-TOKEN':csrf()},
    ...(body !== undefined ? {body: JSON.stringify(body)} : {}),
  })
  return r.json()
}
function toast(t:'success'|'error', msg:string) {
  flash.value = {type:t, msg}
  setTimeout(() => flash.value=null, 4500)
}
function reload() { router.visit(window.location.href, {preserveScroll:true, preserveState:false}) }
function slugPreview(label:string) {
  if (!label) return ''
  let s = label.toLowerCase()
  const m:{[k:string]:string} = {'à':'a','â':'a','é':'e','è':'e','ê':'e','î':'i','ï':'i','ô':'o','ù':'u','û':'u','ç':'c','œ':'oe'}
  for (const [k,v] of Object.entries(m)) s = s.replaceAll(k,v)
  return s.replace(/['']/g,'-').replace(/[^a-z0-9\s-]/g,'').replace(/[\s-]+/g,'-').slice(0,50)
}

// ═══════════════════════════════════════════════════════════════
// CHAIRS : MODAL SYNC AUDIT TYPES (type de mission ou phase)
// ═══════════════════════════════════════════════════════════════
const showAT   = ref(false)
const atMode   = ref<'type'|'form'>('type')
const atTarget = ref<any>(null)
const curType  = ref<any>(null)

// Drag state
let dragSide = ''
let dragItem: any = null

// Sélections left/right
const leftSel  = ref<Set<number>>(new Set())
const rightSel = ref<Set<number>>(new Set())
const atLeft   = ref<any[]>([])
const atRight  = ref<any[]>([])

function openSyncAT(mode:'type'|'form', t:any, target:any) {
  atMode.value   = mode
  curType.value  = t
  atTarget.value = target
  leftSel.value  = new Set()
  rightSel.value = new Set()
  const selIds   = new Set((target.audit_types ?? []).map((a:any) => a.id))
  atRight.value  = allAT.value.filter(a => selIds.has(a.id))
  atLeft.value   = allAT.value.filter(a => !selIds.has(a.id))
  showAT.value   = true
}
const toggleLeftSel  = (id:number) => leftSel.value.has(id)  ? leftSel.value.delete(id)  : leftSel.value.add(id)
const toggleRightSel = (id:number) => rightSel.value.has(id) ? rightSel.value.delete(id) : rightSel.value.add(id)

function moveToRight() {
  const mv = atLeft.value.filter(a => leftSel.value.has(a.id))
  atRight.value.push(...mv)
  atLeft.value = atLeft.value.filter(a => !leftSel.value.has(a.id))
  leftSel.value = new Set()
}
function moveToLeft() {
  const mv = atRight.value.filter(a => rightSel.value.has(a.id))
  atLeft.value.push(...mv)
  atRight.value = atRight.value.filter(a => !rightSel.value.has(a.id))
  rightSel.value = new Set()
}
function moveAllRight() { atRight.value.push(...atLeft.value); atLeft.value = []; leftSel.value = new Set() }
function moveAllLeft()  { atLeft.value.push(...atRight.value); atRight.value = []; rightSel.value = new Set() }

function onDragStart(side:string, item:any, e:DragEvent) {
  dragSide = side; dragItem = item
  e.dataTransfer?.setData('text/plain', item.id)
}
function onDrop(target:'left'|'right', e:DragEvent) {
  e.preventDefault()
  if (!dragItem) return
  if (dragSide === 'left' && target === 'right') { atRight.value.push(dragItem); atLeft.value = atLeft.value.filter(a => a.id !== dragItem.id) }
  else if (dragSide === 'right' && target === 'left') { atLeft.value.push(dragItem); atRight.value = atRight.value.filter(a => a.id !== dragItem.id) }
  dragItem = null
}

async function saveSyncAT() {
  saving.value = true
  try {
    const url = atMode.value === 'type'
      ? `/m/audit.core/param/mission-types/${atTarget.value.id}/audit-types`
      : `/m/audit.core/param/mission-types/${curType.value.id}/phases/${atTarget.value.id}/audit-types`
    const d = await api('POST', url, { audit_type_ids: atRight.value.map((a:any) => a.id) })
    if (d.success) { toast('success', d.message); showAT.value = false; reload() }
    else toast('error', d.error ?? 'Erreur')
  } catch { toast('error','Erreur réseau') }
  saving.value = false
}

// ═══════════════════════════════════════════════════════════════
// CHAIRS INLINE DANS LE MODAL PHASE (pf.audit_type_ids)
// ═══════════════════════════════════════════════════════════════
let phDragSide = ''; let phDragItem: any = null
const phLeftSel  = ref<Set<number>>(new Set())
const phRightSel = ref<Set<number>>(new Set())
const phLeft     = ref<any[]>([])
const phRight    = ref<any[]>([])

function initPhChairs(selIds: number[]) {
  const sel = new Set(selIds)
  phRight.value = allAT.value.filter(a => sel.has(a.id))
  phLeft.value  = allAT.value.filter(a => !sel.has(a.id))
  phLeftSel.value = new Set(); phRightSel.value = new Set()
}
const togglePhLeftSel  = (id:number) => phLeftSel.value.has(id)  ? phLeftSel.value.delete(id)  : phLeftSel.value.add(id)
const togglePhRightSel = (id:number) => phRightSel.value.has(id) ? phRightSel.value.delete(id) : phRightSel.value.add(id)
function phMoveToRight()  { const mv=phLeft.value.filter(a=>phLeftSel.value.has(a.id));  phRight.value.push(...mv); phLeft.value=phLeft.value.filter(a=>!phLeftSel.value.has(a.id));   phLeftSel.value=new Set() }
function phMoveToLeft()   { const mv=phRight.value.filter(a=>phRightSel.value.has(a.id)); phLeft.value.push(...mv);  phRight.value=phRight.value.filter(a=>!phRightSel.value.has(a.id)); phRightSel.value=new Set() }
function phMoveAllRight() { phRight.value.push(...phLeft.value);  phLeft.value=[];  phLeftSel.value=new Set() }
function phMoveAllLeft()  { phLeft.value.push(...phRight.value);  phRight.value=[]; phRightSel.value=new Set() }
function onDragStartPh(side:string, item:any, e:DragEvent) { phDragSide=side; phDragItem=item; e.dataTransfer?.setData('text/plain', item.id) }
function onDropPh(target:'left'|'right', e:DragEvent) {
  e.preventDefault(); if (!phDragItem) return
  if (phDragSide==='left' && target==='right') { phRight.value.push(phDragItem); phLeft.value=phLeft.value.filter(a=>a.id!==phDragItem.id) }
  else if (phDragSide==='right' && target==='left') { phLeft.value.push(phDragItem); phRight.value=phRight.value.filter(a=>a.id!==phDragItem.id) }
  phDragItem=null
}

// ═══════════════════════════════════════════════════════════════
// MODAL PHASE
// ═══════════════════════════════════════════════════════════════
const showPhase  = ref(false)
const editPhase  = ref<any>(null)
const phParent   = ref<any>(null)
const pf         = ref({label:'', description:'', icon:'ti ti-file-description'})

function openAddPhase(t:any, parent?:any) {
  curType.value=t; phParent.value=parent??null; editPhase.value=null
  pf.value = {label:'', description:'', icon:'ti ti-file-description'}
  initPhChairs([])
  if (!expanded.value.has(t.id)) expanded.value.add(t.id)
  showPhase.value=true
}
function openAddSub(t:any, parent:any) { openAddPhase(t, parent) }
function openEditPhase(t:any, ph:any) {
  curType.value=t; editPhase.value=ph; phParent.value=null
  pf.value = {label:ph.label, description:ph.description??'', icon:ph.icon??'ti ti-file-description'}
  initPhChairs((ph.audit_types??[]).map((a:any)=>a.id))
  showPhase.value=true
}
async function savePhase() {
  saving.value = true
  try {
    const payload = {...pf.value, audit_type_ids: phRight.value.map((a:any)=>a.id)}
    let d:any
    if (editPhase.value)
      d = await api('PUT',  `/m/audit.core/param/mission-types/${curType.value.id}/phases/${editPhase.value.id}`, payload)
    else if (phParent.value)
      d = await api('POST', `/m/audit.core/param/mission-types/${curType.value.id}/phases/${phParent.value.id}/sub`, payload)
    else
      d = await api('POST', `/m/audit.core/param/mission-types/${curType.value.id}/phases`, payload)
    if (d.success) { toast('success', d.message??'Enregistré'); showPhase.value=false; reload() }
    else toast('error', d.error??'Erreur')
  } catch { toast('error','Erreur réseau') }
  saving.value = false
}

// ═══════════════════════════════════════════════════════════════
// MODAL TYPE DE MISSION
// ═══════════════════════════════════════════════════════════════
const showType = ref(false)
const editType = ref<any>(null)
const tf = ref({code:'',label:'',description:'',color:'#2563EB',icon:'ti ti-clipboard-list',sort_order:0,is_active:true})
function openAddType() { editType.value=null; tf.value={code:'',label:'',description:'',color:'#2563EB',icon:'ti ti-clipboard-list',sort_order:0,is_active:true}; showType.value=true }
function openEditType(t:any) {
  editType.value=t
  tf.value={code:t.code,label:t.label,description:t.description??'',color:t.color??'#2563EB',icon:t.icon??'ti ti-clipboard-list',sort_order:t.sort_order??0,is_active:!!t.is_active}
  showType.value=true
}
async function saveType() {
  saving.value=true
  try {
    const d = editType.value
      ? await api('PUT',  `/m/audit.core/param/mission-types/${editType.value.id}`, tf.value)
      : await api('POST', '/m/audit.core/param/mission-types', tf.value)
    if (d.success) { toast('success',d.message); showType.value=false; reload() }
    else toast('error',d.error??'Erreur')
  } catch { toast('error','Erreur réseau') }
  saving.value=false
}

// DELETE
const showDel  = ref(false)
const delItem  = ref<any>(null)
const delMode  = ref<'type'|'phase'>('type')
function openDelType(t:any)       { delItem.value=t;  delMode.value='type';  showDel.value=true }
function openDelPhase(t:any,ph:any){ curType.value=t; delItem.value=ph; delMode.value='phase'; showDel.value=true }
async function confirmDel() {
  deleting.value=true
  try {
    const d = delMode.value==='type'
      ? await api('DELETE',`/m/audit.core/param/mission-types/${delItem.value.id}`)
      : await api('DELETE',`/m/audit.core/param/mission-types/${curType.value.id}/phases/${delItem.value.id}`)
    if (d.success) { toast('success',d.message); showDel.value=false; reload() }
    else toast('error',d.error??'Erreur')
  } catch { toast('error','Erreur réseau') }
  deleting.value=false
}

// LOGO
const showLogoM = ref(false)
function openLogo(t:any) { curType.value=t; logoFile.value=null; showLogoM.value=true }
async function uploadLogo() {
  if (!logoFile.value||!curType.value) return
  saving.value=true
  const fd=new FormData(); fd.append('logo',logoFile.value)
  try {
    const r = await fetch(`/m/audit.core/param/mission-types/${curType.value.id}/logo`,
      {method:'POST',headers:{'X-CSRF-TOKEN':csrf()},body:fd})
    const d = await r.json()
    if (d.success) { toast('success','Logo enregistré'); showLogoM.value=false; reload() }
    else toast('error',d.error??'Erreur')
  } catch { toast('error','Erreur réseau') }
  saving.value=false
}
</script>

<style scoped>
/* ── Base ──────────────────────────────────────────── */
.page-header{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:22px 24px 18px}
.page-header__top{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:16px}
.page-title{font-size:1.3rem;font-weight:800;color:#111827;margin:0 0 4px;letter-spacing:-.02em}
.page-desc{font-size:.8rem;color:#6b7280;margin:0}
.badge-module{font-size:.68rem;font-weight:800;letter-spacing:.09em;background:#1e293b;color:#fff;padding:3px 9px;border-radius:4px}
.bc-sep{font-size:.7rem;color:#9ca3af}
.status-chip{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:.72rem;font-weight:600;background:#eff6ff;color:#2563EB;border:1px solid #bfdbfe}
.dot{width:6px;height:6px;border-radius:50%;background:#2563EB}
.stats-row{display:flex;gap:8px;flex-wrap:wrap}
.stat-chip{display:flex;align-items:center;gap:7px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:7px 13px}
.stat-chip__val{font-size:.9rem;font-weight:800;color:#111827}
.stat-chip__lbl{font-size:.7rem;color:#9ca3af}
/* Buttons */
.btn{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:7px;font-size:.8rem;font-weight:600;border:none;cursor:pointer;font-family:inherit;transition:all .15s;white-space:nowrap}
.btn--primary{background:#1e293b;color:#fff}
.btn--primary:hover:not(:disabled){background:#0f172a}
.btn--ghost{background:#fff;color:#374151;border:1px solid #e5e7eb}
.btn--ghost:hover{background:#f9fafb}
.btn--danger{background:#fef2f2;color:#dc2626;border:1px solid #fecaca}
.btn--danger:hover:not(:disabled){background:#fee2e2}
.btn--sm{padding:5px 10px;font-size:.76rem}
.btn:disabled{opacity:.45;cursor:not-allowed}
/* Types list */
.types-list{display:flex;flex-direction:column;gap:8px}
.type-block{background:#fff;border:1px solid #e5e7eb;border-left:3px solid var(--tc,#2563EB);border-radius:10px;overflow:hidden;transition:box-shadow .15s}
.type-block--open{box-shadow:0 4px 16px rgba(0,0,0,.07)}
.type-row{display:flex;align-items:center;justify-content:space-between;padding:12px 14px;cursor:pointer;gap:10px;transition:background .12s}
.type-row:hover{background:#fafafa}
.type-row-l{display:flex;align-items:center;gap:9px;flex:1;min-width:0}
.type-row-r{display:flex;gap:4px;flex-shrink:0}
.chevron{width:18px;height:18px;display:flex;align-items:center;justify-content:center;color:#9ca3af;flex-shrink:0}
.chevron i{transition:transform .2s}
.chevron--open i{transform:rotate(90deg)}
.type-logo{width:34px;height:34px;border-radius:8px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:1rem}
.logo-img{width:100%;height:100%;object-fit:contain;border-radius:6px}
.type-info{}
.badge-code{font-size:.66rem;font-weight:800;letter-spacing:.08em;background:#1e293b;color:#fff;padding:2px 8px;border-radius:5px;display:inline-block}
.badge-off{font-size:.62rem;font-weight:700;background:#f3f4f6;color:#9ca3af;padding:2px 6px;border-radius:9px}
.type-label{display:block;font-size:.88rem;font-weight:700;color:#111827}
.type-meta{display:block;font-size:.7rem;color:#9ca3af;margin-top:1px}
/* Audit type pills */
.at-pill{display:inline-flex;align-items:center;gap:3px;font-size:.6rem;font-weight:800;padding:1px 6px;border-radius:9px;border:1px solid;letter-spacing:.05em}
.at-pill--sm{font-size:.58rem;padding:1px 5px}
/* Star buttons */
.ibtn-star{display:inline-flex;align-items:center;justify-content:center;width:16px;height:16px;border:1px solid #e5e7eb;border-radius:4px;background:transparent;color:#d1d5db;cursor:pointer;font-size:.58rem;transition:all .12s;padding:0;flex-shrink:0}
.ibtn-star:hover{color:#7C3AED;border-color:#c4b5fd;background:#f5f3ff}
.ibtn-star--sm{width:13px;height:13px;font-size:.52rem}
/* Icon buttons */
.ibtn{width:25px;height:25px;display:flex;align-items:center;justify-content:center;background:transparent;border:1px solid transparent;border-radius:5px;cursor:pointer;font-size:.78rem;color:#d1d5db;transition:all .12s;padding:0}
.ibtn--sm{width:19px;height:19px;font-size:.68rem}
.ibtn--add:hover{color:#059669;border-color:#a7f3d0;background:#ecfdf5}
.ibtn--logo:hover{color:#0284c7;border-color:#bae6fd;background:#f0f9ff}
.ibtn--edit:hover{color:#2563EB;border-color:#bfdbfe;background:#eff6ff}
.ibtn--del:hover{color:#dc2626;border-color:#fecaca;background:#fef2f2}
/* Phases */
.phases-panel{border-top:1px solid #f3f4f6;background:#fafafa}
.phases-empty{display:flex;align-items:center;gap:8px;padding:14px 18px;color:#9ca3af;font-size:.8rem}
.link-btn{background:none;border:none;color:#2563EB;cursor:pointer;font-size:.8rem;font-family:inherit;text-decoration:underline;padding:0}
.phase-n1{border-bottom:1px solid #f3f4f6}
.phase-n1:last-child{border-bottom:none}
.phase-row{display:flex;align-items:center;justify-content:space-between;padding:7px 14px;cursor:pointer;gap:8px;transition:background .12s}
.phase-row:hover{background:#f3f4f6}
.phase-row-l{display:flex;align-items:center;gap:6px;flex:1;min-width:0}
.phase-row-r{display:flex;gap:3px;flex-shrink:0}
.chev-sm{width:14px;height:14px;display:flex;align-items:center;justify-content:center;color:#d1d5db;font-size:.65rem;flex-shrink:0}
.chev-sm i{transition:transform .2s}
.chev-sm--open i{transform:rotate(90deg)}
.ph-ico{font-size:.82rem;flex-shrink:0}
.ph-info{min-width:0}
.ph-label{font-size:.78rem;font-weight:600;color:#374151}
.ph-url{display:block;font-size:.6rem;color:#9ca3af;font-family:monospace;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.sub-badge{font-size:.62rem;font-weight:700;background:#e5e7eb;color:#6b7280;padding:1px 6px;border-radius:9px;flex-shrink:0}
.subphases{background:#f3f4f6}
.sub-row{display:flex;align-items:center;gap:6px;padding:5px 14px 5px 44px;transition:background .12s}
.sub-row:hover{background:#ebebeb}
.tree-l{width:1px;background:#e5e7eb;align-self:stretch;flex-shrink:0;margin-right:2px}
.sub-ico{font-size:.75rem;color:#9ca3af;flex-shrink:0}
.add-phase-btn{display:flex;align-items:center;gap:7px;width:100%;padding:9px 18px;background:none;border:none;border-top:1px solid #f3f4f6;color:#9ca3af;font-size:.76rem;cursor:pointer;transition:all .12s;font-family:inherit}
.add-phase-btn:hover{color:#2563EB;background:#eff6ff}
/* ══ CHAIRS ════════════════════════════════════════════ */
.chairs-wrap{display:flex;gap:10px;align-items:stretch;min-height:200px}
.chairs-wrap--compact{min-height:160px}
.chairs-col{flex:1;display:flex;flex-direction:column;min-width:0}
.chairs-col--right{/* mirror */}
.chairs-col__head{display:flex;align-items:center;gap:5px;font-size:.72rem;font-weight:700;color:#374151;background:#f3f4f6;padding:7px 10px;border-radius:7px 7px 0 0;border:1px solid #e5e7eb;border-bottom:none;flex-wrap:wrap}
.chairs-count{margin-left:auto;font-size:.64rem;font-weight:800;background:#e5e7eb;color:#6b7280;padding:1px 6px;border-radius:8px}
.chairs-count--green{background:#d1fae5;color:#059669}
.chairs-hint{font-size:.62rem;color:#9ca3af;font-style:italic;margin-left:4px}
.chairs-list{flex:1;border:1px solid #e5e7eb;border-radius:0 0 7px 7px;padding:6px;overflow-y:auto;min-height:140px;max-height:240px;background:#fff;display:flex;flex-direction:column;gap:4px;transition:background .12s}
.chairs-list--compact{min-height:100px;max-height:180px}
.chairs-list[dragover]{background:#eff6ff;border-color:#bfdbfe}
.chairs-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:5px;height:100%;color:#d1d5db;font-size:.76rem;text-align:center;padding:16px}
.chairs-empty--sm{font-size:.7rem;padding:10px}
.chairs-item{display:flex;align-items:center;gap:7px;padding:7px 9px;border:1px solid #e5e7eb;border-radius:7px;cursor:pointer;transition:all .15s;background:#fff;user-select:none}
.chairs-item:hover{border-color:#d1d5db;background:#f9fafb}
.chairs-item--compact{padding:5px 7px;gap:5px;border-radius:6px}
.chairs-item--active{/* already styled via :style */}
.chairs-item--selected{font-weight:700}
.chairs-item__ico{width:26px;height:26px;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:.8rem;flex-shrink:0}
.chairs-item__info{flex:1;min-width:0}
.chairs-item__code{display:block;font-size:.66rem;font-weight:900;letter-spacing:.08em}
.chairs-item__lbl{display:block;font-size:.7rem;color:#6b7280;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.chairs-item__check{font-size:.7rem;flex-shrink:0;margin-left:auto}
/* Buttons transfert */
.chairs-btns{display:flex;flex-direction:column;justify-content:center;gap:5px;flex-shrink:0}
.chairs-btns--compact{gap:4px}
.transfer-btn{width:30px;height:30px;display:flex;align-items:center;justify-content:center;background:#fff;border:1px solid #e5e7eb;border-radius:7px;cursor:pointer;color:#6b7280;font-size:.75rem;transition:all .15s;padding:0;line-height:1}
.transfer-btn:hover:not(:disabled){background:#f9fafb;border-color:#2563EB;color:#2563EB}
.transfer-btn:disabled{opacity:.3;cursor:not-allowed}
/* Flash */
.flash{position:fixed;top:18px;right:18px;z-index:9999;display:flex;align-items:center;gap:8px;padding:10px 16px;border-radius:8px;font-size:.8rem;font-weight:600;box-shadow:0 4px 18px rgba(0,0,0,.12);border:1px solid transparent}
.flash--success{background:#ecfdf5;color:#059669;border-color:#a7f3d0}
.flash--error{background:#fef2f2;color:#dc2626;border-color:#fecaca}
.flash-t-enter-active,.flash-t-leave-active{transition:all .25s}
.flash-t-enter-from,.flash-t-leave-to{opacity:0;transform:translateX(12px)}
/* Modal */
.overlay{position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,.38);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;padding:20px}
.mbox{background:#fff;border:1px solid #e5e7eb;border-radius:13px;width:100%;max-width:580px;box-shadow:0 20px 55px rgba(0,0,0,.16);display:flex;flex-direction:column;max-height:90vh}
.mbox--sm{max-width:450px}.mbox--md{max-width:640px}
.mbox__h{display:flex;align-items:center;justify-content:space-between;padding:15px 18px;border-bottom:1px solid #f3f4f6;gap:10px}
.mbox__h--danger{background:#fff5f5;border-bottom-color:#fecaca}
.mbox__title{font-size:.92rem;font-weight:700;color:#111827;margin:0}
.mbox__sub{font-size:.72rem;color:#9ca3af;margin:2px 0 0;font-family:monospace}
.mico{width:30px;height:30px;border-radius:7px;background:#f3f4f6;display:flex;align-items:center;justify-content:center;font-size:.85rem;color:#374151;flex-shrink:0}
.mico--danger{background:#fee2e2;color:#dc2626}
.mcls{width:26px;height:26px;display:flex;align-items:center;justify-content:center;background:none;border:none;color:#9ca3af;cursor:pointer;border-radius:5px;transition:all .12s}
.mcls:hover{background:#f3f4f6;color:#111827}
.mbox__b{padding:18px;overflow-y:auto;flex:1}
.mbox__f{display:flex;justify-content:flex-end;gap:7px;padding:12px 18px;border-top:1px solid #f3f4f6;flex-wrap:wrap}
/* Forms */
.fg-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
.fg{display:flex;flex-direction:column;gap:4px}
.fg2{grid-column:span 2}.fg3{grid-column:span 3}
.flbl{font-size:.68rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em}
.req{color:#dc2626}
.fi{background:#fff;border:1px solid #e5e7eb;color:#111827;padding:7px 10px;border-radius:6px;font-size:.81rem;outline:none;transition:border-color .15s;font-family:inherit;width:100%;box-sizing:border-box}
.fi:focus{border-color:#2563EB;box-shadow:0 0 0 3px rgba(37,99,235,.1)}
.fi:disabled{background:#f9fafb;color:#9ca3af;cursor:not-allowed}
.fi-ta{resize:vertical;min-height:60px}
.fi-mono{font-family:ui-monospace,monospace!important}
.fi-color{width:40px;height:36px;padding:2px;border:1px solid #e5e7eb;border-radius:6px;cursor:pointer;flex-shrink:0}
.fchk{display:flex;align-items:center;gap:7px;cursor:pointer;font-size:.81rem;color:#374151}
.fchk input{accent-color:#2563EB}
/* Info bar */
.info-bar{display:flex;align-items:center;gap:7px;font-size:.78rem;color:#374151;background:#eff6ff;border:1px solid #bfdbfe;border-radius:7px;padding:8px 12px;flex-wrap:wrap}
.info-bar--sm{padding:6px 10px;font-size:.74rem}
/* Alert */
.alert-warn{display:flex;align-items:flex-start;gap:8px;padding:11px 13px;background:#fffbeb;border:1px solid #fde68a;border-radius:7px;font-size:.8rem;color:#374151;line-height:1.5}
/* File drop */
.file-drop{border:2px dashed #e5e7eb;border-radius:8px;padding:24px;text-align:center;cursor:pointer;color:#9ca3af;display:flex;flex-direction:column;align-items:center;gap:5px;transition:all .15s}
.file-drop i{font-size:1.7rem}
.file-drop:hover{border-color:#2563EB;background:#eff6ff;color:#374151}
.file-drop p{margin:0;font-size:.82rem;font-weight:500;color:#374151}
.file-drop span{font-size:.7rem}
/* Spinner */
.spin{width:10px;height:10px;border:2px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:spin .6s linear infinite;display:inline-block;flex-shrink:0}
.spin--r{border-color:rgba(220,38,38,.15);border-top-color:#dc2626}
@keyframes spin{to{transform:rotate(360deg)}}
/* Utils */
.d-flex{display:flex}.align-items-center{align-items:center}.align-items-start{align-items:flex-start}
.justify-content-between{justify-content:space-between}.gap-1{gap:4px}.gap-2{gap:8px}.flex-wrap{flex-wrap:wrap}
.flex-1{flex:1}.flex-shrink-0{flex-shrink:0}.min0{min-width:0}
.mb1{margin-bottom:2px}.mb2{margin-bottom:8px}.mb3{margin-bottom:14px}.mb-4{margin-bottom:20px}
.ms2{margin-left:5px}.pb1{padding-bottom:4px}
</style>