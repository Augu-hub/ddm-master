<template>
    <VerticalLayout>
        <div class="cp-shell">

            <!-- ══ HEADER ══ -->
            <header class="cp-hero">
                <div class="cp-mesh"></div>
                <div class="cp-hero-inner">
                    <a href="/mon-espace/dashboard" class="cp-back" title="Retour au tableau de bord">
                        <i class="ti ti-arrow-left"></i>
                    </a>
                    <div class="cp-who">
                        <p class="cp-kicker">Espace Auditeur</p>
                        <h1 class="cp-title"><i class="ti ti-award"></i> Mes Compétences</h1>
                        <div class="cp-meta">
                            <span class="cp-pill cp-code"><i class="ti ti-fingerprint"></i>{{ auditor?.audit_code }}</span>
                            <span class="cp-pill">{{ auditor?.nom_complet }}</span>
                            <span v-if="auditor?.entity" class="cp-pill"><i class="ti ti-building"></i>{{ auditor.entity }}</span>
                        </div>
                    </div>
                    <div class="cp-kpis">
                        <div class="cpk"><span class="cpk-v">{{ stats.total ?? 0 }}</span><span class="cpk-l">Compétences</span></div>
                        <div class="cpk cpk-star"><span class="cpk-v">{{ stats.principales ?? 0 }}</span><span class="cpk-l">Principales</span></div>
                        <div class="cpk cpk-cert"><span class="cpk-v">{{ stats.certifiees ?? 0 }}</span><span class="cpk-l">Certifiées</span></div>
                        <div class="cpk cpk-exp"><span class="cpk-v">{{ stats.expertes ?? 0 }}</span><span class="cpk-l">Niv. 4-5</span></div>
                        <div class="cpk cpk-avg"><span class="cpk-v">{{ stats.niveau_moyen ?? 0 }}<em>/5</em></span><span class="cpk-l">Niveau moyen</span></div>
                    </div>
                </div>
            </header>

            <!-- ══ BODY ══ -->
            <div class="cp-body">
                <div v-if="!categories.length" class="cp-empty">
                    <i class="ti ti-award-off"></i>
                    <p>Aucune compétence enregistrée</p>
                    <span>Le référentiel de compétences est renseigné par le paramétrage des auditeurs.</span>
                </div>

                <section v-for="cat in categories" :key="cat.name" class="cp-cat"
                    :style="`--cc:${cat.color};--ccl:${cat.color}18`">
                    <div class="cpc-head">
                        <span class="cpc-badge" :style="`background:${cat.color}18;color:${cat.color};border-color:${cat.color}35`">
                            {{ cat.code }}
                        </span>
                        <h2 class="cpc-name">{{ cat.name }}</h2>
                        <span class="cpc-count">{{ cat.competencies.length }} compétence(s)</span>
                        <span class="cpc-avg" :style="`color:${cat.color}`">
                            moy. {{ cat.niveau_moyen }}/5
                        </span>
                    </div>

                    <div class="cpc-list">
                        <article v-for="c in cat.competencies" :key="c.id" class="cp-item" :class="{ primary: c.is_primary }">
                            <div class="cpi-main">
                                <div class="cpi-head">
                                    <code class="cpi-code" :style="`color:${cat.color};background:${cat.color}12`">{{ c.code }}</code>
                                    <h3 class="cpi-name">{{ c.name }}</h3>
                                    <span v-if="c.is_primary" class="cpi-primary" title="Compétence principale">
                                        <i class="ti ti-star-filled"></i> Principale
                                    </span>
                                    <span v-if="c.certified_date_fr" class="cpi-cert" :title="`Certifiée le ${c.certified_date_fr}`">
                                        <i class="ti ti-certificate"></i> {{ c.certified_date_fr }}
                                    </span>
                                </div>
                                <p v-if="c.description" class="cpi-desc">{{ c.description }}</p>
                                <p v-if="c.notes" class="cpi-notes"><i class="ti ti-note"></i>{{ c.notes }}</p>
                            </div>

                            <!-- Niveau -->
                            <div class="cpi-level">
                                <div class="cpl-dots">
                                    <span v-for="n in 5" :key="n" class="cpl-dot"
                                        :class="{
                                            on: n <= c.level,
                                            req: n === c.level_required && c.level < c.level_required,
                                        }"
                                        :style="n <= c.level ? `background:${cat.color}` : ''"
                                        :title="n === c.level_required ? `Niveau requis : ${c.level_required}` : `Niveau ${n}`">
                                    </span>
                                </div>
                                <div class="cpl-txt">
                                    <strong :style="`color:${cat.color}`">{{ c.level }}/5</strong>
                                    <span class="cpl-lbl">{{ levelLabel(c.level) }}</span>
                                </div>
                                <span v-if="c.level < c.level_required" class="cpl-gap">
                                    <i class="ti ti-trending-up"></i> Requis : {{ c.level_required }}
                                </span>
                                <span v-else class="cpl-ok"><i class="ti ti-check"></i> Niveau requis atteint</span>
                            </div>
                        </article>
                    </div>
                </section>
            </div>
        </div>
    </VerticalLayout>
</template>

<script setup lang="ts">
import VerticalLayout from '@/layouts/VerticalLayoutAudit.vue';

defineProps({
    auditor:    { type: Object, default: () => ({}) },
    categories: { type: Array as () => any[], default: () => [] },
    stats:      { type: Object, default: () => ({}) },
});

function levelLabel(l: number): string {
    return ({ 1: 'Notions', 2: 'Débutant', 3: 'Confirmé', 4: 'Avancé', 5: 'Expert' } as any)[l] ?? '—';
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,600;9..40,800&family=JetBrains+Mono:wght@400;700&display=swap');

* { box-sizing: border-box; }

.cp-shell {
    font-family: 'DM Sans', sans-serif;
    min-height: calc(100vh - 68px);
    background: #060c1a;
    color: #e2e8f0;
}

/* ══ HERO ══ */
.cp-hero {
    position: relative;
    padding: 26px 26px 22px;
    background: linear-gradient(135deg, #0a1628 0%, #0f1e3a 45%, #0d1527 100%);
    border-bottom: 1px solid rgba(255,255,255,.06);
    overflow: hidden;
}
.cp-mesh {
    position: absolute; inset: 0; pointer-events: none;
    background:
        radial-gradient(ellipse 55% 80% at 85% 40%, rgba(124,58,237,.12) 0%, transparent 70%),
        radial-gradient(ellipse 40% 60% at 10% 90%, rgba(37,99,235,.09) 0%, transparent 60%);
}
.cp-hero-inner {
    position: relative;
    display: flex; align-items: center; gap: 18px; flex-wrap: wrap;
}
.cp-back {
    width: 38px; height: 38px; border-radius: 11px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    background: rgba(37,99,235,.12); border: 1px solid rgba(37,99,235,.3);
    color: #60a5fa; text-decoration: none; transition: all .14s;
}
.cp-back:hover { background: #2563eb; color: #fff; }
.cp-who { display: flex; flex-direction: column; gap: 4px; min-width: 0; flex: 1; }
.cp-kicker { margin: 0; font-size: .64rem; text-transform: uppercase; letter-spacing: .12em; color: #a78bfa; font-weight: 700; }
.cp-title { margin: 0; font-size: 1.3rem; font-weight: 800; color: #f1f5f9; display: flex; align-items: center; gap: 9px; }
.cp-title i { color: #a78bfa; }
.cp-meta { display: flex; gap: 6px; flex-wrap: wrap; }
.cp-pill {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: .66rem; color: #94a3b8;
    background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.08);
    padding: 2px 9px; border-radius: 20px;
}
.cp-code { font-family: 'JetBrains Mono', monospace; color: #7dd3fc; }

.cp-kpis { display: flex; gap: 8px; flex-wrap: wrap; }
.cpk {
    display: flex; flex-direction: column; align-items: center;
    min-width: 84px; padding: 9px 13px; border-radius: 12px;
    background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.07);
}
.cpk-v { font-size: 1.05rem; font-weight: 800; color: #f1f5f9; }
.cpk-v em { font-style: normal; font-size: .68rem; color: #64748b; }
.cpk-l { font-size: .56rem; text-transform: uppercase; letter-spacing: .07em; color: #64748b; }
.cpk-star .cpk-v { color: #fbbf24; }
.cpk-cert .cpk-v { color: #34d399; }
.cpk-exp  .cpk-v { color: #60a5fa; }
.cpk-avg  .cpk-v { color: #a78bfa; }

/* ══ BODY ══ */
.cp-body { padding: 18px 22px 40px; display: flex; flex-direction: column; gap: 16px; max-width: 1100px; }

.cp-empty {
    display: flex; flex-direction: column; align-items: center; gap: 8px;
    padding: 70px 20px; color: #334155;
    background: #0d1627; border: 1px solid rgba(255,255,255,.07); border-radius: 14px;
}
.cp-empty i { font-size: 2.2rem; }
.cp-empty p { margin: 0; font-size: .92rem; font-weight: 700; color: #475569; }
.cp-empty span { font-size: .72rem; text-align: center; max-width: 380px; }

/* Catégorie */
.cp-cat {
    background: #0d1627; border: 1px solid rgba(255,255,255,.07);
    border-radius: 14px; overflow: hidden;
}
.cpc-head {
    display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
    padding: 13px 17px; border-bottom: 1px solid rgba(255,255,255,.06);
    background: linear-gradient(90deg, var(--ccl), transparent 60%);
}
.cpc-badge {
    font-family: 'JetBrains Mono', monospace; font-size: .64rem; font-weight: 700;
    padding: 3px 9px; border-radius: 6px; border: 1px solid;
}
.cpc-name { margin: 0; font-size: .92rem; font-weight: 800; color: #f1f5f9; flex: 1; }
.cpc-count { font-size: .64rem; color: #64748b; }
.cpc-avg { font-size: .7rem; font-weight: 800; }

.cpc-list { display: flex; flex-direction: column; }
.cp-item {
    display: flex; align-items: stretch; justify-content: space-between; gap: 18px;
    padding: 13px 17px; border-bottom: 1px solid rgba(255,255,255,.04);
    transition: background .12s;
}
.cp-item:last-child { border-bottom: none; }
.cp-item:hover { background: rgba(255,255,255,.02); }
.cp-item.primary { background: rgba(251,191,36,.03); }

.cpi-main { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 4px; }
.cpi-head { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.cpi-code {
    font-family: 'JetBrains Mono', monospace; font-size: .62rem; font-weight: 700;
    padding: 2px 7px; border-radius: 4px;
}
.cpi-name { margin: 0; font-size: .82rem; font-weight: 700; color: #e2e8f0; }
.cpi-primary {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: .58rem; font-weight: 800; padding: 2px 8px; border-radius: 10px;
    background: rgba(251,191,36,.14); color: #fbbf24;
}
.cpi-cert {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: .58rem; font-weight: 700; padding: 2px 8px; border-radius: 10px;
    background: rgba(52,211,153,.12); color: #34d399;
}
.cpi-desc { margin: 0; font-size: .7rem; color: #64748b; line-height: 1.4; }
.cpi-notes {
    margin: 0; display: flex; align-items: center; gap: 5px;
    font-size: .66rem; color: #7dd3fc; font-style: italic;
}

/* Niveau */
.cpi-level {
    display: flex; flex-direction: column; align-items: flex-end; justify-content: center;
    gap: 5px; flex-shrink: 0; min-width: 150px;
}
.cpl-dots { display: flex; gap: 4px; }
.cpl-dot {
    width: 16px; height: 8px; border-radius: 4px;
    background: rgba(255,255,255,.08);
    transition: background .2s;
    position: relative;
}
.cpl-dot.req::after {
    content: '';
    position: absolute; inset: -3px;
    border: 1px dashed rgba(248,113,113,.6); border-radius: 6px;
}
.cpl-txt { display: flex; align-items: baseline; gap: 6px; }
.cpl-txt strong { font-size: .82rem; font-weight: 800; }
.cpl-lbl { font-size: .62rem; color: #64748b; }
.cpl-gap {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: .58rem; font-weight: 700; color: #f87171;
}
.cpl-ok {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: .58rem; font-weight: 700; color: #34d399;
}

@media (max-width: 640px) {
    .cp-item { flex-direction: column; gap: 10px; }
    .cpi-level { align-items: flex-start; }
}
</style>
