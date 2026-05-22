#!/usr/bin/env node
/**
 * generateRapportWord.js
 * ══════════════════════
 * Script Node.js appelé par Laravel pour générer le rapport .docx.
 * Usage : node generateRapportWord.js --input data.json --output rapport.docx
 *
 * À placer dans : resources/js/audit/generateRapportWord.js
 * npm install docx  (dans le dossier du projet ou globalement)
 */

'use strict';

const fs   = require('fs');
const path = require('path');

// Résolution de 'docx' :
// 1. node_modules local du projet (npm install docx)
// 2. npm global (npm install -g docx)
// 3. chemin explicite via env DOCX_MODULE_PATH
function resolveDocx() {
  // Ordre de priorité
  const candidates = [
    // Variable d'environnement explicite
    process.env.DOCX_MODULE_PATH,
    // node_modules local au projet (remonte jusqu'à la racine Laravel)
    path.resolve(__dirname, '../../../node_modules/docx'),
    path.resolve(__dirname, '../../../../node_modules/docx'),
    // npm global Windows
    path.join(process.env.APPDATA || '', 'npm', 'node_modules', 'docx'),
    // npm global Unix/Mac
    '/usr/local/lib/node_modules/docx',
    '/usr/lib/node_modules/docx',
    // Herd Windows (npm global dans le home)
    path.join(process.env.USERPROFILE || process.env.HOME || '', '.npm-global', 'lib', 'node_modules', 'docx'),
    path.join(process.env.HOME || '', '.npm', 'lib', 'node_modules', 'docx'),
  ].filter(Boolean);

  for (const candidate of candidates) {
    try {
      return require(candidate);
    } catch (_) {
      // essai suivant
    }
  }

  // Dernier recours : require direct (si dans node_modules du CWD)
  try { return require('docx'); } catch (_) {}

  throw new Error(
    'Module "docx" introuvable.\n' +
    'Installer avec : npm install docx  (dans le dossier du projet Laravel)\n' +
    'Ou globalement : npm install -g docx\n' +
    'Ou définir DOCX_MODULE_PATH dans .env'
  );
}

const {
  Document, Packer, Paragraph, TextRun, Table, TableRow, TableCell,
  Header, Footer, AlignmentType, BorderStyle, WidthType, ShadingType,
  VerticalAlign, PageNumber, PageBreak, LevelFormat, TabStopType, TabStopPosition,
} = resolveDocx();

// ── Args ─────────────────────────────────────────────────────────
const args = process.argv.slice(2);
const getArg = (flag) => { const i = args.indexOf(flag); return i !== -1 ? args[i + 1] : null; };
const inputPath  = getArg('--input')  ?? args[0];
const outputPath = getArg('--output') ?? args[1];

if (!inputPath || !outputPath) {
  console.error('Usage: node generateRapportWord.js --input data.json --output rapport.docx');
  process.exit(1);
}

const DATA = JSON.parse(fs.readFileSync(inputPath, 'utf8'));

// ── Constantes ───────────────────────────────────────────────────
const CONTENT_W = 9921; // A4 avec marges ~2cm
const MARGINS   = { top: 1134, right: 851, bottom: 1134, left: 1134 };
const C = {
  navy: '1A3A5C', blue2: '2C5282', blue3: 'E8EEF5', blue4: 'DCE8F5',
  yellow: 'FFFDE7', gray: 'F5F7FA', white: 'FFFFFF', text: '1A1A1A',
};

// ── Helpers ──────────────────────────────────────────────────────
const bdr = (color = 'CCCCCC', sz = 4) => ({
  top:    { style: BorderStyle.SINGLE, size: sz, color },
  bottom: { style: BorderStyle.SINGLE, size: sz, color },
  left:   { style: BorderStyle.SINGLE, size: sz, color },
  right:  { style: BorderStyle.SINGLE, size: sz, color },
});
const noBdr = { top: { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' }, bottom: { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' }, left: { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' }, right: { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' } };
const shade = (fill) => ({ fill, type: ShadingType.CLEAR, color: 'auto' });
const mkCell = (children, w, opts = {}) => new TableCell({
  borders: opts.borders ?? bdr(),
  width: { size: w, type: WidthType.DXA },
  shading: opts.fill ? shade(opts.fill) : undefined,
  margins: { top: 80, bottom: 80, left: 120, right: 120 },
  verticalAlign: opts.va ?? VerticalAlign.TOP,
  columnSpan: opts.span,
  children,
});
const mkP = (text, opts = {}) => new Paragraph({
  alignment: opts.align ?? AlignmentType.LEFT,
  spacing: opts.spacing ?? { before: 0, after: 0 },
  pageBreakBefore: opts.pageBreak,
  border: opts.border,
  children: opts.runs ?? [new TextRun({
    text, bold: opts.bold, italic: opts.italic,
    size: opts.size ?? 18, color: opts.color ?? C.text, font: 'Arial',
  })],
});
const mkRun = (text, opts = {}) => new TextRun({
  text, bold: opts.bold, italic: opts.italic,
  size: opts.size ?? 18, color: opts.color ?? C.text, font: 'Arial',
});
const badgeRun = (imp) => {
  const map = { critique: ['[CRITIQUE]','8B1A1A'], haute: ['[SIGNIFICATIF]','7A4A0A'], moyenne: ['[PEU SIGNIFICATIF]','0F5A3A'], basse: ['[MAINTENANCE]','1A4A8A'] };
  const [text, color] = map[imp] ?? map.basse;
  return mkRun(text, { bold: true, color, size: 16 });
};
const fmtDate = (d) => d ? new Date(d).toLocaleDateString('fr-FR') : '—';
const sectionHdr = (text) => new Table({
  width: { size: CONTENT_W, type: WidthType.DXA }, columnWidths: [CONTENT_W],
  rows: [new TableRow({ children: [mkCell([mkP(text, { bold: true, color: C.white, size: 22 })], CONTENT_W, { fill: C.navy, borders: noBdr })] })],
});
const subTitle = (text) => new Paragraph({
  spacing: { before: 200, after: 100 },
  border: { bottom: { style: BorderStyle.SINGLE, size: 4, color: C.navy, space: 1 } },
  children: [mkRun(text, { bold: true, color: C.navy, size: 18 })],
});
const subSubTitle = (text) => new Paragraph({
  spacing: { before: 140, after: 60 },
  children: [mkRun(text, { bold: true, color: '2C5282', size: 17 })],
});
const editZone = (label, content) => new Table({
  width: { size: CONTENT_W, type: WidthType.DXA }, columnWidths: [CONTENT_W],
  rows: [new TableRow({ children: [mkCell([
    mkP(`✏ ${label}`, { italic: true, color: '888888', size: 14 }),
    mkP(content || '(non renseigné)', { italic: true, color: '555555', size: 17 }),
  ], CONTENT_W, { fill: 'FAFCFF', borders: bdr('B0C4DE', 6) })] })],
});

// ── Build ────────────────────────────────────────────────────────
const { mission, entity, equipe = [], constats = [], tableauObjectifs = [],
        opinion = {}, statsConstats = {}, pointsForts = [],
        objectifsSpecifiques = [], editable = {} } = DATA;

const dm = equipe.find(e => e.role === 'DM');
const cm = equipe.find(e => e.role === 'CM');
const children = [];

// COUVERTURE
children.push(
  mkP(' ', { spacing: { before: 0, after: 400 } }),
  new Table({ width: { size: CONTENT_W, type: WidthType.DXA }, columnWidths: [CONTENT_W], rows: [new TableRow({ children: [mkCell([
    mkP('Direction de l\'Audit Interne', { italic: true, color: '666666', size: 16 }),
    mkP('RAPPORT D\'AUDIT INTERNE', { bold: true, color: C.navy, size: 36, align: AlignmentType.CENTER }),
  ], CONTENT_W, { fill: 'F0F4F8', borders: { top: { style: BorderStyle.SINGLE, size: 12, color: C.navy }, bottom: { style: BorderStyle.SINGLE, size: 12, color: C.navy }, left: noBdr.left, right: noBdr.right } })] })] }),
  mkP(' ', { spacing: { before: 0, after: 200 } }),
  new Table({ width: { size: CONTENT_W, type: WidthType.DXA }, columnWidths: [Math.round(CONTENT_W*.35), Math.round(CONTENT_W*.65)], rows: [
    ['N° Rapport', `RAP-${mission?.id}-${new Date().getFullYear()}`],
    ['N° FPM', mission?.numero_fpm ?? '—'],
    ['Date d\'émission', fmtDate(new Date().toISOString())],
    ['Version', 'Définitive'],
  ].map(([k, v]) => new TableRow({ children: [
    mkCell([mkP(k, { bold: true, color: '475569', size: 16 })], Math.round(CONTENT_W*.35), { fill: 'F8FAFC' }),
    mkCell([mkP(v, { size: 17 })], Math.round(CONTENT_W*.65)),
  ]})) }),
  mkP(' ', { spacing: { before: 0, after: 160 } }),
  new Table({ width: { size: CONTENT_W, type: WidthType.DXA }, columnWidths: [CONTENT_W], rows: [new TableRow({ children: [mkCell([
    mkP('Intitulé de la mission', { bold: true, color: '888888', size: 15, italic: true }),
    mkP(mission?.libelle ?? '—', { bold: true, color: C.navy, size: 24 }),
  ], CONTENT_W, { fill: 'FAFAFA' })] })] }),
  mkP(' ', { spacing: { before: 0, after: 140 } }),
  new Table({ width: { size: CONTENT_W, type: WidthType.DXA }, columnWidths: [Math.round(CONTENT_W/2), Math.round(CONTENT_W/2)], rows: [
    [['Objectif général', mission?.objectif ?? '—'], ['Entité auditée', entity?.entity_name ?? '—']],
    [['Dates de l\'audit', `${fmtDate(mission?.date_debut)} au ${fmtDate(mission?.date_fin)}`], ['Lieu(x)', mission?.lieux ?? '—']],
    [['Chef de Mission', cm?.nom_complet ?? '—'], ['Directeur de Mission', dm?.nom_complet ?? '—']],
  ].map(row => new TableRow({ children: row.map(([k, v]) => mkCell([
    mkP(k.toUpperCase(), { bold: true, color: '888888', size: 14 }),
    mkP(v, { size: 17 }),
  ], Math.round(CONTENT_W/2), { fill: 'FAFAFA' })) })) }),
  mkP(' ', { spacing: { before: 0, after: 140 } }),
  new Table({ width: { size: CONTENT_W, type: WidthType.DXA }, columnWidths: [CONTENT_W], rows: [new TableRow({ children: [mkCell([
    mkP('CONFIDENTIEL — Ce rapport s\'adresse uniquement aux personnes désignées. Toute diffusion non autorisée est interdite.', { italic: true, color: '7A2800', size: 16 })
  ], CONTENT_W, { fill: 'FFF3EE', borders: bdr('E8A870', 4) })] })] }),
  mkP(' ', { pageBreak: true }),
);

// SECTION 1
children.push(sectionHdr('Section 1 — Résumé Exécutif'), mkP(' ', { spacing: { before: 0, after: 100 } }));
children.push(subTitle('1.1 — Opinion Générale'));
children.push(new Paragraph({ spacing: { before: 80, after: 60 }, children: [mkRun('Niveau d\'opinion : ', { bold: true, size: 17 }), mkRun(opinion.niveau ?? '—', { bold: true, color: C.navy, size: 18 })] }));
children.push(editZone('Opinion Générale (zone modifiable)', editable?.opinion || opinion?.description || ''));

// Stats
const sc = statsConstats;
const cw4 = [CONTENT_W/4, CONTENT_W/4, CONTENT_W/4, CONTENT_W/4].map(Math.round);
children.push(
  mkP(' ', { spacing: { before: 0, after: 80 } }),
  new Table({ width: { size: CONTENT_W, type: WidthType.DXA }, columnWidths: cw4, rows: [new TableRow({ children: [
    mkCell([mkP(String(sc.critique??0), { bold: true, color: '8B1A1A', size: 40, align: AlignmentType.CENTER }), mkP('Critique', { color: '8B1A1A', size: 15, align: AlignmentType.CENTER })], cw4[0]),
    mkCell([mkP(String(sc.significatif??0), { bold: true, color: '7A4A0A', size: 40, align: AlignmentType.CENTER }), mkP('Significatif', { color: '7A4A0A', size: 15, align: AlignmentType.CENTER })], cw4[1]),
    mkCell([mkP(String(sc.peu_significatif??0), { bold: true, color: '0F5A3A', size: 40, align: AlignmentType.CENTER }), mkP('Peu significatif', { color: '0F5A3A', size: 15, align: AlignmentType.CENTER })], cw4[2]),
    mkCell([mkP(String(sc.total??0), { bold: true, color: '1A4A8A', size: 40, align: AlignmentType.CENTER }), mkP('Total constats', { color: '1A4A8A', size: 15, align: AlignmentType.CENTER })], cw4[3]),
  ]})] }),
  mkP(' ', { spacing: { before: 0, after: 160 } }),
);

// 1.2 Résumé constats
children.push(subTitle('1.2 — Résumé des Constats'));
tableauObjectifs.forEach((obj, idx) => {
  const objC = constats.filter(c => c.obj_num === obj.num);
  if (!objC.length) return;
  children.push(
    subSubTitle(`1.2.${idx+1} — ${obj.objectif}`),
    ...(obj.axe ? [mkP(obj.axe, { italic: true, color: '888888', size: 15, spacing: { before: 0, after: 50 } })] : []),
    ...objC.map(c => new Paragraph({
      spacing: { before: 50, after: 30 }, indent: { left: 280 },
      border: { left: { style: BorderStyle.SINGLE, size: 12, color: c.importance==='critique'?'C0392B':c.importance==='haute'?'E67E22':c.importance==='moyenne'?'27AE60':'2980B9', space: 8 } },
      children: [
        mkRun(`${c.num_frap}  `, { bold: true, color: '888888', size: 16 }),
        mkRun((c.probleme || (c.fait_constats??'').substring(0, 120)), { size: 17 }),
        mkRun('  '), badgeRun(c.importance ?? 'basse'),
      ],
    })),
    mkP(' ', { spacing: { before: 0, after: 80 } }),
  );
});

// 1.3 Plan d'actions
children.push(subTitle('1.3 — Plan d\'Actions'));
const colsPA = [Math.round(CONTENT_W*.05), Math.round(CONTENT_W*.27), Math.round(CONTENT_W*.30), Math.round(CONTENT_W*.16), Math.round(CONTENT_W*.10), Math.round(CONTENT_W*.12)];
tableauObjectifs.forEach((obj, oIdx) => {
  const acts = [];
  constats.filter(c => c.obj_num === obj.num).forEach(c => {
    (c.recommandation || '').split('\n').filter(r => r.trim()).forEach(reco => {
      acts.push({ fait: c.probleme || (c.fait_constats||'').substring(0,80), reco: reco.trim(), code: c.num_frap, responsable: c.personne_responsable, echeance: fmtDate(c.date_echeance), imp: c.importance });
    });
  });
  if (!acts.length) return;
  children.push(
    subSubTitle(`1.3.${oIdx+1} — ${obj.objectif}`),
    new Table({ width: { size: CONTENT_W, type: WidthType.DXA }, columnWidths: colsPA, rows: [
      new TableRow({ children: ['#','Point de faiblesse','Mesures recommandées','Responsable','Échéance','Priorité'].map((h,i) => mkCell([mkP(h, { bold: true, color: C.white, size: 15 })], colsPA[i], { fill: C.navy, borders: bdr(C.navy) })) }),
      ...acts.map((a, i) => new TableRow({ children: [
        mkCell([mkP(String(i+1), { align: AlignmentType.CENTER, bold: true, size: 16, color: '555555' })], colsPA[0], { fill: i%2?C.gray:C.white }),
        mkCell([mkP(a.code??'', { size: 14, color: '999999', italic: true }), mkP(a.fait??'—', { size: 16 })], colsPA[1], { fill: i%2?C.gray:C.white }),
        mkCell([mkP(a.reco??'—', { size: 16 })], colsPA[2], { fill: i%2?C.gray:C.white }),
        mkCell([mkP(a.responsable??'—', { size: 16 })], colsPA[3], { fill: i%2?C.gray:C.white }),
        mkCell([mkP(a.echeance??'—', { size: 16 })], colsPA[4], { fill: i%2?C.gray:C.white }),
        mkCell([new Paragraph({ spacing:{before:0,after:0}, children:[badgeRun(a.imp??'basse')] })], colsPA[5], { fill: i%2?C.gray:C.white }),
      ]})),
    ]}),
    mkP(' ', { spacing: { before: 0, after: 100 } }),
  );
});

// 1.4 → 1.8 zones éditables
const freeZones = [
  ['1.4 — Résumé des Points Forts',             editable?.points_forts || (pointsForts.length ? pointsForts.map(f=>`• ${f}`).join('\n') : '')],
  ["1.5 — Énoncé des Normes d'Audit",           editable?.normes || "L'audit a été conduit conformément aux Normes Internationales pour la Pratique Professionnelle de l'Audit Interne (IIA)."],
  ["1.6 — Limites de l'Audit",                  editable?.limites || ''],
  ['1.7 — Observations de la Structure Auditée',editable?.observations || ''],
  ['1.8 — Difficultés Rencontrées',             editable?.difficultes || ''],
];
freeZones.forEach(([lbl, content]) => {
  children.push(subTitle(lbl), editZone(lbl, content), mkP(' ', { spacing: { before: 0, after: 80 } }));
});

// SECTION 2 — Tableau 3 colonnes
children.push(mkP(' ', { pageBreak: true }), sectionHdr('Section 2 — Tableau de Maîtrise des Risques'), mkP(' ', { spacing: { before: 0, after: 100 } }));
const colsT = [Math.round(CONTENT_W*.44), Math.round(CONTENT_W*.24), Math.round(CONTENT_W*.32)];

tableauObjectifs.forEach(obj => {
  const objC = constats.filter(c => c.obj_num === obj.num);
  children.push(
    new Table({ width: { size: CONTENT_W, type: WidthType.DXA }, columnWidths: [CONTENT_W], rows: [new TableRow({ children: [mkCell([
      new Paragraph({ spacing:{before:0,after:0}, children: [mkRun(`${obj.num}  `, { bold:true, color:C.white, size:18, font:'Courier New' }), mkRun(obj.objectif, { bold:true, color:C.white, size:19 }), mkRun(`  [${(obj.priorite??'').toUpperCase()}]`, { color:'A0C4FF', size:16 })] })
    ], CONTENT_W, { fill: C.navy, borders: noBdr })] })] }),
    new Table({ width: { size: CONTENT_W, type: WidthType.DXA }, columnWidths: [CONTENT_W], rows: [new TableRow({ children: [mkCell([
      new Paragraph({ spacing:{before:0,after:0}, children: [
        ...(obj.axe ? [mkRun(`Axe : ${obj.axe}`, {size:15, color:C.navy})] : []),
        ...(obj.risque_code ? [mkRun(`  |  Risque : ${obj.risque_code} — ${obj.risque_libelle}`, {size:15, color:C.navy})] : []),
      ]})
    ], CONTENT_W, { fill: C.blue3, borders: bdr('B0C4DE', 4) })] })] }),
  );

  const tRows = [new TableRow({ children: ['ACTIVITÉS / CRITÈRES / CONSTATS','NIVEAU DE MAÎTRISE','PLAN D\'ACTION'].map((h,i) => mkCell([mkP(h, { bold:true, color:C.white, size:15 })], colsT[i], { fill:'2C5282', borders: bdr('2C5282') })) })];

  obj.tests?.forEach(test => {
    tRows.push(new TableRow({ children: [mkCell([new Paragraph({ spacing:{before:0,after:0}, children: [mkRun(`Test ${test.ref} : `, {bold:true, color:C.navy, size:16}), mkRun(test.libelle??'', {bold:true, color:C.navy, size:16}), ...(test.auditeur ? [mkRun(`  (${test.auditeur})`, {italic:true, color:'64748B', size:14})] : [])] })], colsT[0]+colsT[1]+colsT[2], { fill: C.blue4, borders: bdr('B0C4DE',4), span:3 })] }));

    if (test.procedures?.length) {
      tRows.push(new TableRow({ children: [mkCell([mkP('Procédures :', {bold:true, color:'555555', size:14}), ...test.procedures.map(pr => new Paragraph({ spacing:{before:30,after:0}, indent:{left:200}, children:[mkRun(`• ${pr}`, {size:15,color:'555555'})] }))], colsT[0]+colsT[1]+colsT[2], { fill:'F5F8FF', borders:bdr('DCE8F5',4), span:3 })] }));
    }

    const tC = objC.filter(c => c.test_ref === test.ref);
    tC.forEach((c, ci) => {
      const imp = c.importance ?? 'basse';
      const causes  = (c.causes||'').split('\n').filter(s=>s.trim());
      const impacts = (c.impacts||'').split('\n').filter(s=>s.trim());
      const recos   = (c.recommandation||'').split('\n').filter(s=>s.trim());
      tRows.push(new TableRow({ children: [
        mkCell([
          mkP(c.num_frap??'', {bold:true, color:'888888', size:14, italic:true}),
          mkP('FAIT CONSTATÉ', {bold:true, color:'888888', size:13}),
          mkP(c.fait_constats??'—', {size:16}),
          ...(c.probleme ? [mkP('PROBLÈME', {bold:true, color:'888888', size:13, spacing:{before:80,after:0}}), mkP(c.probleme, {size:16})] : []),
          ...(causes.length ? [mkP('CAUSES', {bold:true, color:'888888', size:13, spacing:{before:80,after:0}}), ...causes.map(ca=>mkP(`• ${ca.replace(/^[•–-]\s*/,'')}`, {size:15,color:'666666'}))] : []),
          ...(impacts.length ? [mkP('IMPACTS', {bold:true, color:'888888', size:13, spacing:{before:80,after:0}}), ...impacts.map(im=>mkP(`• ${im.replace(/^[•–-]\s*/,'')}`, {size:15,color:'666666'}))] : []),
        ], colsT[0], { fill: ci%2 ? 'F8FAFF' : C.white }),
        mkCell([
          new Paragraph({ spacing:{before:0,after:60}, children:[badgeRun(imp)] }),
          mkP(`Statut : ${c.statut==='validated'?'Validé':c.statut==='in_review'?'En revue':'En cours'}`, {size:15, color:'555555'}),
          ...(c.commentaires_audite ? [mkP('Note audité :', {bold:true, color:'888888', size:13, spacing:{before:80,after:0}}), mkP(c.commentaires_audite, {italic:true, color:'555555', size:15})] : []),
        ], colsT[1], { fill: ci%2 ? 'F8FAFF' : C.white }),
        mkCell([
          mkP('✏ RECOMMANDATION(S)', {bold:true, color:'888888', size:13}),
          ...recos.map(r => mkP(`→ ${r.replace(/^[•–-]\s*/,'')}`, {size:16})),
          ...(c.personne_responsable ? [mkP(`Responsable : ${c.personne_responsable}`, {size:15, color:'444444', spacing:{before:80,after:0}})] : []),
          ...(c.date_echeance ? [mkP(`Échéance : ${fmtDate(c.date_echeance)}`, {size:15, color:'444444'})] : []),
        ], colsT[2], { fill: 'FAFCFF', borders: bdr('B0C4DE', 6) }),
      ]}));
    });

    if (!tC.length) {
      tRows.push(new TableRow({ children: [mkCell([mkP('Aucun constat enregistré.', {italic:true, color:'999999', size:15})], colsT[0]+colsT[1]+colsT[2], { fill: C.gray, span:3 })] }));
    }
  });

  children.push(new Table({ width:{size:CONTENT_W,type:WidthType.DXA}, columnWidths:colsT, rows:tRows }), mkP(' ', { spacing:{before:0,after:200} }));
});

// SECTION 3 — Annexes
children.push(mkP(' ', { pageBreak: true }), sectionHdr('Section 3 — Annexes'), mkP(' ', { spacing:{before:0,after:100} }), subTitle('Annexe 1A — Identification des objectifs'));
const cA = [Math.round(CONTENT_W*.07), Math.round(CONTENT_W*.18), Math.round(CONTENT_W*.40), Math.round(CONTENT_W*.35)];
children.push(
  new Table({ width:{size:CONTENT_W,type:WidthType.DXA}, columnWidths:cA, rows: [
    new TableRow({ children: ['#','Axe RADO','Objectif de contrôle','Risque associé'].map((h,i) => mkCell([mkP(h, {bold:true, color:C.white, size:15})], cA[i], { fill:C.navy, borders:bdr(C.navy) })) }),
    ...objectifsSpecifiques.map((obj, i) => {
      const oD = tableauObjectifs.find(o => o.num === obj.num);
      return new TableRow({ children: [
        mkCell([mkP(obj.num, {bold:true, color:C.navy, size:16, align:AlignmentType.CENTER, font:'Courier New'})], cA[0], { fill: i%2?C.gray:C.white }),
        mkCell([mkP(obj.axe??'—', {size:16})], cA[1], { fill: i%2?C.gray:C.white }),
        mkCell([mkP(obj.objectif, {size:16})], cA[2], { fill: i%2?C.gray:C.white }),
        mkCell([...(oD?.risque_code ? [mkP(`${oD.risque_code} — ${oD.risque_libelle??''}`, {size:15,color:'555555'})] : []), ...(oD?.source ? [mkP(oD.source, {italic:true,color:'888888',size:14})] : [])], cA[3], { fill: i%2?C.gray:C.white }),
      ]});
    }),
  ]}),
  mkP(' '), subTitle('Annexe 1B — Critères d\'évaluation'),
);
const cB = [Math.round(CONTENT_W*.08), Math.round(CONTENT_W*.92)];
children.push(
  new Table({ width:{size:CONTENT_W,type:WidthType.DXA}, columnWidths:cB, rows: [
    new TableRow({ children: ['#','Critères d\'évaluation'].map((h,i) => mkCell([mkP(h, {bold:true, color:C.white, size:15})], cB[i], { fill:C.navy, borders:bdr(C.navy) })) }),
    ...objectifsSpecifiques.map((obj, i) => new TableRow({ children: [
      mkCell([mkP(obj.num, {bold:true, color:C.navy, size:16, align:AlignmentType.CENTER, font:'Courier New'})], cB[0], { fill: i%2?C.gray:C.white }),
      mkCell([mkP(obj.criteres_evaluation??'—', {size:16, color:'444444'})], cB[1], { fill: i%2?C.gray:C.white }),
    ]})),
  ]}),
  mkP(' '), subTitle('Annexe 2 — Équipe d\'audit'),
);
const cEq = [Math.round(CONTENT_W*.40), Math.round(CONTENT_W*.20), Math.round(CONTENT_W*.40)];
children.push(
  new Table({ width:{size:CONTENT_W,type:WidthType.DXA}, columnWidths:cEq, rows: [
    new TableRow({ children: ['Nom & Prénom','Rôle','Code auditeur'].map((h,i) => mkCell([mkP(h, {bold:true, color:C.white, size:15})], cEq[i], { fill:C.navy, borders:bdr(C.navy) })) }),
    ...equipe.map((m, i) => new TableRow({ children: [
      mkCell([mkP(m.nom_complet, {size:17})], cEq[0], { fill: i%2?C.gray:C.white }),
      mkCell([mkP(m.role, {bold:true, color:C.navy, size:16, align:AlignmentType.CENTER})], cEq[1], { fill: i%2?C.gray:C.white }),
      mkCell([mkP(m.audit_code??'—', {size:16, font:'Courier New'})], cEq[2], { fill: i%2?C.gray:C.white }),
    ]})),
  ]}),
  mkP(' '), subTitle('Signatures'),
);
const cSig = [Math.round(CONTENT_W/3), Math.round(CONTENT_W/3), Math.round(CONTENT_W - Math.round(CONTENT_W/3)*2)];
children.push(new Table({ width:{size:CONTENT_W,type:WidthType.DXA}, columnWidths:cSig, rows: [new TableRow({ children: [
  mkCell([mkP('Chef de Mission', {bold:true, color:'888888', size:15}), mkP(cm?.nom_complet??'—', {bold:true, size:16}), mkP('Visa : _______________', {italic:true, color:'888888', size:14})], cSig[0]),
  mkCell([mkP('Directeur de Mission', {bold:true, color:'888888', size:15}), mkP(dm?.nom_complet??'—', {bold:true, size:16}), mkP('Visa : _______________', {italic:true, color:'888888', size:14})], cSig[1]),
  mkCell([mkP('Responsable Entité Auditée', {bold:true, color:'888888', size:15}), mkP('_______________', {size:16}), mkP('Visa : _______________', {italic:true, color:'888888', size:14})], cSig[2]),
]})] }));

// ── Assemble & Write ─────────────────────────────────────────────
const doc = new Document({
  styles: { default: { document: { run: { font: 'Arial', size: 18 } } } },
  sections: [{
    properties: { page: { size: { width: 11906, height: 16838 }, margin: MARGINS } },
    headers: { default: new Header({ children: [
      new Table({ width:{size:CONTENT_W,type:WidthType.DXA}, columnWidths:[Math.round(CONTENT_W*.6),Math.round(CONTENT_W*.4)], rows:[new TableRow({ children:[
        mkCell([mkP(`Audit Interne — ${mission?.code_mission??''}`, {size:15, color:'64748B'})], Math.round(CONTENT_W*.6), { borders:{...noBdr, bottom:{style:BorderStyle.SINGLE, size:4, color:'E2E8F0'}} }),
        mkCell([mkP('CONFIDENTIEL', {bold:true, color:'8B1A1A', size:14, align:AlignmentType.RIGHT})], Math.round(CONTENT_W*.4), { borders:{...noBdr, bottom:{style:BorderStyle.SINGLE, size:4, color:'E2E8F0'}} }),
      ]})]})
    ]}) },
    footers: { default: new Footer({ children: [new Paragraph({
      spacing:{before:80,after:0},
      border:{top:{style:BorderStyle.SINGLE, size:4, color:'E2E8F0', space:1}},
      tabStops:[{type:TabStopType.RIGHT, position:TabStopPosition.MAX}],
      children:[
        mkRun(`${mission?.libelle??''} — `, {size:14, color:'94A3B8'}),
        mkRun('Page ', {size:14, color:'94A3B8'}),
        new TextRun({children:[PageNumber.CURRENT], size:14, color:'94A3B8'}),
        mkRun('\t', {size:14}),
        mkRun(new Date().toLocaleDateString('fr-FR'), {size:14, color:'94A3B8'}),
      ],
    })] }) },
    children,
  }],
});

Packer.toBuffer(doc).then(buf => {
  fs.writeFileSync(outputPath, buf);
  console.log(`✅ Rapport généré : ${outputPath}`);
}).catch(err => {
  console.error('❌ Erreur génération :', err.message);
  process.exit(1);
});
