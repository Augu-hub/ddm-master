<template>
  <div class="rpt-wrap">

    <!-- TOOLBAR (non imprimée) -->
    <div class="toolbar no-print">
      <div class="tb-left">
        <Link :href="route('risk.core.eval-sessions.index')" class="tb-btn"><i class="ti ti-arrow-left"></i> Sessions</Link>
        <span class="tb-sess"><i class="ti ti-versions"></i> {{ session.name }} <span v-if="session.year">· {{ session.year }}</span>
          <span v-if="!session.is_frozen" class="tb-live" title="Session non gelée : données du registre vivant">état vivant</span>
          <span v-else class="tb-frozen" title="Session gelée">gelée le {{ session.snapshot_at }}</span>
        </span>
      </div>
      <div class="tb-right">
        <select v-model="procSel" class="tb-sel" @change="onProcChange" title="Périmètre du rapport">
          <option :value="null">Tous les processus</option>
          <option v-for="p in processes" :key="p.id" :value="p.id">{{ p.code }} — {{ p.name }}</option>
        </select>
        <button :class="['tb-btn', editing?'tb-btn--on':'']" @click="editing=!editing">
          <i :class="editing?'ti ti-eye':'ti ti-pencil'"></i> {{ editing ? 'Aperçu' : 'Éditer' }}
        </button>
        <button class="tb-btn tb-save" :disabled="saving" @click="save"><i :class="saving?'ti ti-loader-2 ti-spin':'ti ti-device-floppy'"></i> Enregistrer</button>
        <Link :href="route('risk.core.evaluation.cartographie', { session_id: session.id })" class="tb-btn"><i class="ti ti-map-2"></i> Cartographie</Link>
        <button class="tb-btn tb-print" @click="print"><i class="ti ti-printer"></i> Imprimer / PDF</button>
      </div>
    </div>

    <!-- DOCUMENT -->
    <div class="doc">

      <!-- COUVERTURE -->
      <header class="cover">
        <h1>Rapport de gestion et de cartographie des risques</h1>
        <div class="cover-meta">
          <span><b>Version :</b> <Ed v-model="c.version" :editing="editing" ph="1.0" inline/></span>
          <span><b>Date :</b> <Ed v-model="c.date" :editing="editing" ph="jj/mm/aaaa" inline/></span>
          <span><b>Période :</b> <Ed v-model="c.periode" :editing="editing" :ph="String(session.year||'')" inline/></span>
          <span><b>Entité :</b> <Ed v-model="c.entite" :editing="editing" ph="Nom de l'entité" inline/></span>
        </div>
      </header>

      <!-- 1. SYNTHÈSE EXÉCUTIVE -->
      <section>
        <h2>1. Synthèse exécutive</h2>

        <h3>1.1. Message de la Direction</h3>
        <Ed v-model="c.message_direction" :editing="editing" rows="3"/>

        <h3>1.2. Profil de risque global</h3>
        <table class="tbl">
          <thead><tr><th>Indicateur</th><th>Période {{ report.periodeN1 || 'N-1' }}</th><th>Période {{ report.periodeN || 'N' }}</th><th>Évolution</th></tr></thead>
          <tbody>
            <tr>
              <td><span class="dot" style="background:#dc2626"></span> Risques résiduels Élevés (Rouges)</td>
              <td>{{ nn(report.profil.eleve.n1) }}</td><td class="b">{{ nn(report.profil.eleve.n) }}</td>
              <td><Evo :delta="report.profil.eleve.delta" good="down"/></td>
            </tr>
            <tr>
              <td><span class="dot" style="background:#eab308"></span> Risques résiduels Moyens (Jaunes)</td>
              <td>{{ nn(report.profil.moyen.n1) }}</td><td class="b">{{ nn(report.profil.moyen.n) }}</td>
              <td><Evo :delta="report.profil.moyen.delta" good="down"/></td>
            </tr>
            <tr>
              <td><span class="dot" style="background:#16a34a"></span> Risques résiduels Faibles (Verts)</td>
              <td>{{ nn(report.profil.faible.n1) }}</td><td class="b">{{ nn(report.profil.faible.n) }}</td>
              <td><Evo :delta="report.profil.faible.delta" good="up"/></td>
            </tr>
            <tr>
              <td>Taux d'avancement du plan de mitigation</td>
              <td>{{ report.profil.mitigation.n1!=null ? report.profil.mitigation.n1+' %' : '—' }}</td>
              <td class="b">{{ report.profil.mitigation.n!=null ? report.profil.mitigation.n+' %' : '—' }}</td>
              <td><Evo :delta="report.profil.mitigation.delta" good="up" suffix=" pts"/></td>
            </tr>
          </tbody>
        </table>
        <p class="lbl">Conclusion</p>
        <Ed v-model="c.conclusion" :editing="editing" rows="2"
            :ph="`Le dispositif de contrôle interne a permis de réduire le niveau de risque résiduel. Les efforts doivent se poursuivre sur les ${report.profil.eleve.n} risque(s) critiques identifiés.`"/>
      </section>

      <!-- 2. GOUVERNANCE -->
      <section>
        <h2>2. Gouvernance & dispositif</h2>
        <h3>2.1. Déclaration d'appétit pour le risque</h3>
        <Ed v-model="c.appetit" :editing="editing" rows="2"/>
        <h3>2.2. Rôles et responsabilités</h3>
        <table class="tbl">
          <thead><tr><th style="width:32%">Acteur</th><th>Responsabilité</th><th v-if="editing" class="no-print" style="width:34px"></th></tr></thead>
          <tbody>
            <tr v-for="(r,i) in c.roles" :key="i">
              <td><Ed v-model="r.acteur" :editing="editing" inline ph="Acteur"/></td>
              <td><Ed v-model="r.resp" :editing="editing" inline ph="Responsabilité"/></td>
              <td v-if="editing" class="no-print"><button class="x" @click="c.roles.splice(i,1)"><i class="ti ti-x"></i></button></td>
            </tr>
          </tbody>
        </table>
        <button v-if="editing" class="add no-print" @click="c.roles.push({acteur:'',resp:''})"><i class="ti ti-plus"></i> Ajouter un rôle</button>
        <h3>2.3. Méthodologie</h3>
        <ul class="bul">
          <li>Risque inhérent = Impact × Probabilité</li>
          <li>Risque résiduel = Risque inhérent − (Prévention + Protection)</li>
        </ul>
      </section>

      <!-- 3. CARTOGRAPHIE -->
      <section>
        <h2>3. Cartographie des risques</h2>
        <h3>3.1. Répartition résiduelle par zone de criticité</h3>
        <div class="zdist">
          <div v-for="z in report.zones" :key="z.label" class="zrow">
            <span class="zlbl"><span class="dot" :style="{background:z.color_code}"></span>{{ z.label }} <span class="zrange">({{ z.min_score }}–{{ z.max_score }})</span></span>
            <div class="zbar"><div class="zfill" :style="{width: zpct(z.label)+'%', background:z.color_code}"></div></div>
            <span class="zval">{{ report.zoneDist[z.label]||0 }}</span>
          </div>
        </div>
        <p class="hint no-print"><i class="ti ti-info-circle"></i> Matrice interactive complète : bouton « Cartographie » ci-dessus.</p>

        <h3>3.2. Synthèse par catégorie de risque</h3>
        <table class="tbl">
          <thead><tr><th>Catégorie</th><th style="width:90px">Nb risques</th><th style="width:120px">Élevés (Rouge)</th><th style="width:90px">Part</th></tr></thead>
          <tbody>
            <tr v-for="cat in report.categories" :key="cat.categorie">
              <td>{{ cat.categorie }}</td>
              <td>{{ cat.total }}</td>
              <td><span v-if="cat.eleves" class="chip-red">{{ cat.eleves }}</span><span v-else>0</span></td>
              <td>{{ cat.total ? Math.round(cat.eleves/cat.total*100) : 0 }} %</td>
            </tr>
            <tr v-if="!report.categories.length"><td colspan="4" class="empty">Aucun risque</td></tr>
          </tbody>
        </table>
      </section>

      <!-- 4. PLAN DE MITIGATION -->
      <section>
        <h2>4. Plan de mitigation et de traitement</h2>
        <h3>4.1. Stratégies de réponse (4T)</h3>
        <ul class="bul">
          <li><b>Traiter</b> : mettre en place des contrôles efficaces.</li>
          <li><b>Tolérer</b> : accepter le risque dans les limites définies.</li>
          <li><b>Transférer</b> : assurer ou externaliser le risque.</li>
          <li><b>Terminer</b> : abandonner l'activité génératrice du risque.</li>
        </ul>

        <h3>4.2. Plan d'actions prioritaires (risques résiduels élevés)</h3>
        <table class="tbl">
          <thead><tr><th>Code</th><th>Risque</th><th>Action prioritaire</th><th>Responsable</th><th style="width:90px">Échéance</th><th style="width:70px">Taux</th></tr></thead>
          <tbody>
            <tr v-for="a in report.actions" :key="a.code">
              <td><span class="chip-code">{{ a.code_risk }}</span></td>
              <td>{{ a.risk_libelle }}</td>
              <td>{{ a.title }}</td>
              <td>{{ a.responsable || '—' }}</td>
              <td>{{ fmtDate(a.target_date) }}</td>
              <td><b>{{ a.progress||0 }} %</b></td>
            </tr>
            <tr v-if="!report.actions.length"><td colspan="6" class="empty">Aucune action prioritaire en cours sur un risque élevé</td></tr>
          </tbody>
        </table>

        <h3>4.3. Suivi global des plans d'action</h3>
        <table class="tbl tbl-narrow">
          <tbody>
            <tr><td>Nombre total d'actions planifiées</td><td class="b">{{ report.suivi.total }}</td></tr>
            <tr><td>Actions achevées (100 %)</td><td class="b">{{ report.suivi.done }}</td></tr>
            <tr><td>Actions en cours (50–99 %)</td><td class="b">{{ report.suivi.ongoing }}</td></tr>
            <tr><td>Actions à initier (&lt; 50 %)</td><td class="b">{{ report.suivi.todo }}</td></tr>
            <tr class="hl"><td>Taux d'avancement global</td><td class="b">{{ report.suivi.taux }} %</td></tr>
          </tbody>
        </table>
      </section>

      <!-- 5. INDICATEURS -->
      <section>
        <h2>5. Indicateurs & rapport d'activités</h2>
        <h3>5.1. Tableau de bord des indicateurs clés (KPI)</h3>
        <table class="tbl">
          <thead><tr><th>Indicateur</th><th style="width:90px">Valeur</th><th style="width:90px">Seuil</th><th style="width:90px">Statut</th></tr></thead>
          <tbody>
            <tr v-for="k in report.kpi" :key="k.label">
              <td>{{ k.label }}</td><td class="b">{{ k.valeur }}</td><td>{{ k.seuil }}</td>
              <td><span :class="['status', k.ok?'st-ok':'st-ko']"><span class="sdot"></span>{{ k.ok?'Vert':'Vigilance' }}</span></td>
            </tr>
          </tbody>
        </table>

        <h3>5.2. Bibliothèque des incidents</h3>
        <table class="tbl">
          <thead><tr><th style="width:90px">Date</th><th>Incident</th><th style="width:110px">Coût</th><th style="width:90px">Statut</th></tr></thead>
          <tbody>
            <tr v-for="(inc,i) in report.incidents" :key="i">
              <td>{{ fmtDate(inc.date_incident || inc.created_at) }}</td>
              <td>{{ inc.titre || inc.libelle || inc.description || '—' }}</td>
              <td>{{ money(inc.cout ?? inc.cout_estime ?? inc.montant) }}</td>
              <td>{{ inc.statut || inc.status || '—' }}</td>
            </tr>
            <tr v-if="!report.incidents.length"><td colspan="4" class="empty">Aucun incident recensé</td></tr>
          </tbody>
        </table>
      </section>

      <!-- 6. PERSPECTIVES -->
      <section>
        <h2>6. Perspectives et plans d'amélioration</h2>
        <h3>6.1. Orientations pour la période à venir</h3>
        <EditList v-model="c.perspectives" :editing="editing" ordered
          :placeholder-items="['Renforcer la cybersécurité et la protection des données.','Automatiser le suivi des plans d\'action via le logiciel.','Sensibiliser le personnel à la culture du risque.']"/>
        <h3>6.2. Amélioration du dispositif</h3>
        <EditList v-model="c.ameliorations" :editing="editing"
          :placeholder-items="['Intégration d\'un module de veille réglementaire.','Mise en place d\'indicateurs avancés (early warning).','Formation des propriétaires de risques à la méthodologie COSO.']"/>
      </section>

      <!-- ANNEXES -->
      <section>
        <h2>Annexes</h2>
        <h3>Annexe A — Glossaire</h3>
        <ul class="bul">
          <li><b>Risque inhérent</b> : risque brut avant contrôle.</li>
          <li><b>Risque résiduel</b> : risque après prise en compte des contrôles.</li>
          <li><b>Appétit pour le risque</b> : niveau de risque que l'organisation accepte.</li>
        </ul>
        <h3>Annexe B — Critères de cotation</h3>
        <div class="cot">
          <table class="tbl">
            <thead><tr><th style="width:60px">Niveau</th><th>Impact</th></tr></thead>
            <tbody><tr v-for="im in report.cotation.impacts" :key="'i'+im.score"><td><span class="chip" :style="{background:im.color_code}">{{ im.score }}</span></td><td>{{ im.label }}<span v-if="im.description" class="desc"> — {{ im.description }}</span></td></tr></tbody>
          </table>
          <table class="tbl">
            <thead><tr><th style="width:60px">Niveau</th><th>Probabilité / Fréquence</th></tr></thead>
            <tbody><tr v-for="fr in report.cotation.frequencies" :key="'f'+fr.score"><td><span class="chip" :style="{background:fr.color_code}">{{ fr.score }}</span></td><td>{{ fr.label }}<span v-if="fr.description" class="desc"> — {{ fr.description }}</span></td></tr></tbody>
          </table>
        </div>
      </section>

      <footer class="foot">Rapport généré depuis DIADDEM RISK · session « {{ session.name }} » · {{ report.zoneDist && Object.values(report.zoneDist).reduce((a,b)=>a+b,0) }} risque(s) résiduel(s) cartographié(s)</footer>
    </div>

    <Transition name="fl"><div v-if="flash" class="flash"><i class="ti ti-check-circle"></i> {{ flash }}</div></Transition>
  </div>
</template>

<script setup>
import { reactive, ref, h } from 'vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
  session:   { type: Object, default: () => ({}) },
  report:    { type: Object, default: () => ({}) },
  content:   { type: Object, default: () => ({}) },
  processes: { type: Array,  default: () => [] },
  processId: { type: Number, default: null },
})

const procSel = ref(props.processId ?? null)
const onProcChange = () => router.get(route('risk.core.eval-sessions.report', props.session.id),
  procSel.value ? { process: procSel.value } : {}, { preserveScroll: true })

const editing = ref(false)
const saving = ref(false)
const flash = ref('')
const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || ''

const c = reactive({
  version: props.content.version || '1.0',
  date: props.content.date || '',
  periode: props.content.periode || (props.session.year ? String(props.session.year) : ''),
  entite: props.content.entite || '',
  message_direction: props.content.message_direction || "Conformément à notre politique de maîtrise des risques, le présent rapport dresse un état des lieux de notre exposition et des actions menées pour préserver nos objectifs stratégiques.",
  conclusion: props.content.conclusion || '',
  appetit: props.content.appetit || "Nous acceptons un risque modéré pour les activités innovantes, mais une tolérance zéro pour les risques de fraude et de non-conformité majeure.",
  roles: props.content.roles || [
    { acteur: "Conseil / Comité d'Audit", resp: "Valide la politique et l'appétit pour le risque" },
    { acteur: 'Direction Générale', resp: 'Pilote le dispositif et alloue les ressources' },
    { acteur: 'Risk Manager', resp: 'Anime la cartographie, produit le reporting' },
    { acteur: 'Propriétaires de risques', resp: 'Identifient, évaluent et traitent les risques opérationnels' },
  ],
  perspectives: props.content.perspectives || [],
  ameliorations: props.content.ameliorations || [],
})

const nn = (v) => v == null ? '—' : v
const zpct = (label) => {
  const max = Math.max(1, ...Object.values(props.report.zoneDist || { x: 1 }))
  return Math.round(((props.report.zoneDist?.[label] || 0) / max) * 100)
}
const fmtDate = (d) => d ? new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' }) : '—'
const money = (v) => (v == null || v === '') ? '—' : new Intl.NumberFormat('fr-FR').format(v)

const save = async () => {
  saving.value = true
  try {
    const r = await fetch(route('risk.core.eval-sessions.report.save', props.session.id), {
      method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify({ content: { ...c } }),
    })
    const d = await r.json()
    flash.value = d.message || 'Enregistré ✓'; setTimeout(() => flash.value = '', 3000)
    editing.value = false
  } catch { flash.value = 'Erreur réseau'; setTimeout(() => flash.value = '', 3000) }
  finally { saving.value = false }
}
const print = () => window.print()

// ── Petits composants inline ──────────────────────────────────────────────
const Ed = (p, { emit }) => {
  if (!p.editing) {
    const txt = (p.modelValue ?? '') || (p.inline ? '' : '')
    return p.inline
      ? h('span', txt || h('span', { class: 'ph' }, p.ph || '…'))
      : h('p', { class: 'rich' }, (p.modelValue || p.ph || ''))
  }
  return h(p.inline ? 'input' : 'textarea', {
    class: p.inline ? 'ed-inline' : 'ed-area',
    value: p.modelValue, rows: p.rows || 3, placeholder: p.ph || '',
    onInput: (e) => emit('update:modelValue', e.target.value),
  })
}
Ed.props = ['modelValue', 'editing', 'inline', 'rows', 'ph']
Ed.emits = ['update:modelValue']

const EditList = (p, { emit }) => {
  const items = p.modelValue && p.modelValue.length ? p.modelValue : (p.editing ? [] : p.placeholderItems)
  if (!p.editing) {
    return h(p.ordered ? 'ol' : 'ul', { class: 'bul' }, items.map((t) => h('li', t)))
  }
  const list = p.modelValue && p.modelValue.length ? p.modelValue : []
  return h('div', { class: 'elist' }, [
    ...list.map((t, i) => h('div', { class: 'erow' }, [
      h('input', { class: 'ed-inline', value: t, placeholder: 'Élément…', onInput: (e) => { const n = [...list]; n[i] = e.target.value; emit('update:modelValue', n) } }),
      h('button', { class: 'x', onClick: () => { const n = [...list]; n.splice(i, 1); emit('update:modelValue', n) } }, h('i', { class: 'ti ti-x' })),
    ])),
    h('button', { class: 'add', onClick: () => emit('update:modelValue', [...list, '']) }, [h('i', { class: 'ti ti-plus' }), ' Ajouter']),
  ])
}
EditList.props = ['modelValue', 'editing', 'ordered', 'placeholderItems']
EditList.emits = ['update:modelValue']

// Evolution badge
const Evo = (p) => {
  if (p.delta == null) return h('span', { class: 'muted' }, '—')
  const improved = p.good === 'down' ? p.delta < 0 : p.delta > 0
  const worsened = p.good === 'down' ? p.delta > 0 : p.delta < 0
  const arrow = p.delta < 0 ? '↓' : (p.delta > 0 ? '↑' : '=')
  const cls = improved ? 'evo-good' : (worsened ? 'evo-bad' : 'evo-eq')
  return h('span', { class: ['evo', cls] }, `${arrow} ${Math.abs(p.delta)}${p.suffix || ''}`)
}
Evo.props = ['delta', 'good', 'suffix']
</script>

<style scoped>
.rpt-wrap{background:#e2e8f0;min-height:100vh;padding-bottom:40px;font-family:'Inter',system-ui,sans-serif;color:#1e293b;}
.toolbar{position:sticky;top:0;z-index:50;display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 18px;background:#0f172a;flex-wrap:wrap;}
.tb-left,.tb-right{display:flex;align-items:center;gap:8px;flex-wrap:wrap;}
.tb-sess{color:#cbd5e1;font-size:12px;font-weight:600;display:flex;align-items:center;gap:6px;}
.tb-live{font-size:9px;font-weight:700;background:#166534;color:#bbf7d0;padding:2px 7px;border-radius:8px;}
.tb-frozen{font-size:9px;font-weight:700;background:#1e3a8a;color:#bfdbfe;padding:2px 7px;border-radius:8px;}
.tb-btn{display:flex;align-items:center;gap:5px;padding:7px 13px;background:rgba(255,255,255,.08);color:#e2e8f0;border:1px solid rgba(255,255,255,.14);border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;text-decoration:none;}
.tb-btn:hover{background:rgba(255,255,255,.16);}
.tb-btn--on{background:#4f46e5;border-color:#4f46e5;}
.tb-sel{padding:7px 10px;background:rgba(255,255,255,.08);color:#e2e8f0;border:1px solid rgba(255,255,255,.14);border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;max-width:230px;}
.tb-sel option{color:#0f172a;}
.tb-save{background:#0d9488;border-color:#0d9488;}
.tb-print{background:#2563eb;border-color:#2563eb;}

.doc{max-width:920px;margin:20px auto;background:#fff;padding:44px 54px;box-shadow:0 4px 24px rgba(0,0,0,.12);}
.cover{border-bottom:3px solid #0f172a;padding-bottom:18px;margin-bottom:24px;}
.cover h1{font-size:26px;font-weight:800;color:#0f172a;margin:0 0 12px;line-height:1.2;}
.cover-meta{display:flex;flex-wrap:wrap;gap:18px;font-size:12px;color:#475569;}
.cover-meta b{color:#0f172a;}

section{margin-bottom:26px;}
h2{font-size:18px;font-weight:800;color:#0f172a;border-bottom:2px solid #e2e8f0;padding-bottom:6px;margin:22px 0 12px;}
h3{font-size:13.5px;font-weight:700;color:#334155;margin:16px 0 7px;}

.tbl{width:100%;border-collapse:collapse;font-size:12px;margin:6px 0;}
.tbl th{text-align:left;padding:7px 10px;background:#f1f5f9;border:1px solid #e2e8f0;font-size:10.5px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.03em;}
.tbl td{padding:7px 10px;border:1px solid #e2e8f0;vertical-align:top;}
.tbl .b{font-weight:800;color:#0f172a;}
.tbl .hl td{background:#f8fafc;font-weight:800;}
.tbl-narrow{max-width:420px;}
.empty{text-align:center;color:#94a3b8;padding:14px!important;}
.dot{display:inline-block;width:10px;height:10px;border-radius:3px;margin-right:6px;vertical-align:middle;}
.lbl{font-weight:700;color:#334155;font-size:12px;margin:12px 0 4px;}

.evo{font-weight:800;font-size:11.5px;padding:2px 8px;border-radius:8px;white-space:nowrap;}
.evo-good{background:#dcfce7;color:#15803d;}.evo-bad{background:#fee2e2;color:#dc2626;}.evo-eq{background:#f1f5f9;color:#64748b;}
.muted{color:#cbd5e1;}

.bul{margin:4px 0 4px 20px;font-size:12.5px;line-height:1.8;}
.zdist{display:flex;flex-direction:column;gap:7px;margin:8px 0;}
.zrow{display:flex;align-items:center;gap:10px;font-size:12px;}
.zlbl{width:210px;font-weight:600;color:#334155;}
.zrange{color:#94a3b8;font-size:10px;font-weight:400;}
.zbar{flex:1;height:16px;background:#f1f5f9;border-radius:8px;overflow:hidden;}
.zfill{height:100%;border-radius:8px;min-width:2px;transition:width .3s;}
.zval{width:34px;text-align:right;font-weight:800;color:#0f172a;}
.hint{font-size:11px;color:#64748b;display:flex;align-items:center;gap:5px;margin-top:6px;}

.chip-code{font-family:monospace;font-size:10px;font-weight:700;color:#4338ca;background:#ede9fe;padding:1px 6px;border-radius:4px;}
.chip-red{display:inline-block;background:#fee2e2;color:#dc2626;font-weight:800;padding:1px 9px;border-radius:8px;}
.chip{display:inline-flex;align-items:center;justify-content:center;width:24px;height:24px;border-radius:6px;color:#fff;font-weight:800;font-size:12px;}
.desc{color:#64748b;font-weight:400;}
.status{display:inline-flex;align-items:center;gap:5px;font-weight:700;font-size:11px;}
.sdot{width:9px;height:9px;border-radius:50%;}
.st-ok{color:#16a34a;}.st-ok .sdot{background:#16a34a;}
.st-ko{color:#d97706;}.st-ko .sdot{background:#d97706;}
.cot{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
@media(max-width:640px){.cot{grid-template-columns:1fr;}}
.foot{margin-top:26px;padding-top:12px;border-top:1px solid #e2e8f0;font-size:10px;color:#94a3b8;text-align:center;}

/* Édition */
.rich{white-space:pre-wrap;font-size:12.5px;line-height:1.7;color:#334155;margin:4px 0;}
.ph{color:#cbd5e1;font-style:italic;}
.ed-area{width:100%;border:1px solid #c7d2fe;border-radius:7px;padding:8px 10px;font-size:12.5px;font-family:inherit;line-height:1.6;background:#fbfbff;}
.ed-inline{border:1px solid #c7d2fe;border-radius:5px;padding:3px 7px;font-size:12px;font-family:inherit;background:#fbfbff;min-width:120px;}
.ed-area:focus,.ed-inline:focus{outline:none;border-color:#6366f1;box-shadow:0 0 0 3px #c7d2fe66;}
.elist{display:flex;flex-direction:column;gap:5px;}
.erow{display:flex;gap:6px;align-items:center;}
.erow .ed-inline{flex:1;}
.x{width:26px;height:26px;border:1px solid #fca5a5;background:#fff;color:#dc2626;border-radius:6px;cursor:pointer;flex-shrink:0;}
.x:hover{background:#fee2e2;}
.add{display:inline-flex;align-items:center;gap:5px;margin-top:6px;padding:5px 11px;border:1px dashed #a5b4fc;background:#eef2ff;color:#4338ca;border-radius:7px;font-size:11px;font-weight:700;cursor:pointer;}

.flash{position:fixed;bottom:20px;right:20px;z-index:9999;display:flex;align-items:center;gap:8px;padding:11px 18px;border-radius:12px;background:#dcfce7;color:#15803d;border:1px solid #86efac;font-size:12px;font-weight:700;box-shadow:0 4px 16px rgba(0,0,0,.15);}
.fl-enter-active,.fl-leave-active{transition:all .2s;}.fl-enter-from,.fl-leave-to{opacity:0;transform:translateX(20px);}
@keyframes ti-spin{to{transform:rotate(360deg);}}.ti-spin{animation:ti-spin .7s linear infinite;display:inline-block;}

/* IMPRESSION */
@media print{
  .no-print{display:none!important;}
  .rpt-wrap{background:#fff;padding:0;}
  .doc{box-shadow:none;margin:0;max-width:100%;padding:0 8mm;}
  section{page-break-inside:avoid;}
  h2{page-break-after:avoid;}
}
</style>
