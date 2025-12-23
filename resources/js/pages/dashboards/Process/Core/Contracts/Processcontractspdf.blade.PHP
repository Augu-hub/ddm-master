<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
        }

        .container {
            padding: 20px;
            max-width: 900px;
            margin: 0 auto;
        }

        .header {
            margin-bottom: 20px;
            border-bottom: 3px solid #2E75B6;
            padding-bottom: 15px;
        }

        .header-title {
            font-size: 20px;
            font-weight: bold;
            color: #2E75B6;
            margin-bottom: 10px;
        }

        .header-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            font-size: 10px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .label {
            font-weight: bold;
            color: #555;
            min-width: 200px;
        }

        .value {
            color: #000;
            font-weight: 500;
        }

        .section-title {
            background-color: #2E75B6;
            color: white;
            padding: 8px 12px;
            margin-top: 20px;
            margin-bottom: 15px;
            font-weight: bold;
            font-size: 12px;
            border-radius: 3px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table thead {
            background-color: #D9E1F2;
            border-top: 2px solid #2E75B6;
            border-bottom: 2px solid #2E75B6;
        }

        table th {
            padding: 10px;
            text-align: left;
            font-weight: bold;
            color: #2E75B6;
            border-right: 1px solid #AAA;
            font-size: 11px;
        }

        table th:last-child {
            border-right: none;
        }

        table td {
            padding: 8px 10px;
            border-bottom: 1px solid #DDD;
            border-right: 1px solid #DDD;
            vertical-align: top;
            font-size: 10px;
        }

        table td:last-child {
            border-right: none;
        }

        table tbody tr:nth-child(even) {
            background-color: #F5F5F5;
        }

        table tbody tr:hover {
            background-color: #F0F0F0;
        }

        .empty-cell {
            color: #999;
            font-style: italic;
        }

        .indicators-section {
            margin-top: 30px;
            page-break-inside: avoid;
        }

        .indicator-box {
            background-color: #F0F0F0;
            border-left: 4px solid #2E75B6;
            padding: 12px;
            margin-bottom: 10px;
            page-break-inside: avoid;
        }

        .indicator-title {
            font-weight: bold;
            color: #2E75B6;
            margin-bottom: 5px;
            font-size: 11px;
        }

        .indicator-content {
            color: #333;
            font-size: 10px;
            line-height: 1.6;
        }

        .footer {
            margin-top: 40px;
            padding-top: 15px;
            border-top: 1px solid #CCC;
            font-size: 9px;
            color: #666;
            text-align: center;
        }

        .date-info {
            color: #999;
            font-size: 9px;
            margin-top: 10px;
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            .container {
                padding: 10px;
            }
            .section-title {
                page-break-after: avoid;
            }
            table {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- HEADER -->
        <div class="header">
            <div class="header-title">📋 CONTRAT D'INTERFACES</div>
            <div class="header-info">
                <div>
                    <div class="info-row">
                        <span class="label">Processus :</span>
                        <span class="value">{{ $process->code ?? 'N/A' }} — {{ $process->name ?? '' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Finalité :</span>
                        <span class="value">{{ $contract->purpose ?? 'Non définie' }}</span>
                    </div>
                </div>
                <div>
                    <div class="info-row">
                        <span class="label">Propriétaire :</span>
                        <span class="value">{{ $contract->owner ?? 'Non défini' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Statut :</span>
                        <span class="value">{{ ucfirst($contract->status ?? 'draft') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- DONNÉES DE SORTIE -->
        <div class="section-title">DONNÉES DE SORTIE</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 35%">Données de Sortie</th>
                    <th style="width: 20%">Utilisateur / Fonction</th>
                    <th style="width: 20%">Attentes Utilisateurs</th>
                    <th style="width: 25%">Acteur (Qui sait faire ?)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($outputs as $output)
                    <tr>
                        <td><strong>{{ $output['label'] ?? '' }}</strong></td>
                        <td>
                            @if($output['user'] ?? null)
                                {{ $output['user'] }}
                            @else
                                <span class="empty-cell">—</span>
                            @endif
                        </td>
                        <td>
                            @if($output['expectations'] ?? null)
                                {{ $output['expectations'] }}
                            @else
                                <span class="empty-cell">—</span>
                            @endif
                        </td>
                        <td>
                            @if($output['actor'] ?? null)
                                {{ $output['actor'] }}
                            @else
                                <span class="empty-cell">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="empty-cell">Aucune donnée de sortie définie</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- INDICATEURS -->
        <div class="indicators-section">
            <div class="section-title">INDICATEURS</div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <!-- Indicateurs d'activité -->
                <div class="indicator-box">
                    <div class="indicator-title">📊 Indicateurs d'Activité</div>
                    <div class="indicator-content">
                        @if($contract->activity_indicators && count($contract->activity_indicators) > 0)
                            @foreach($contract->activity_indicators as $indicator)
                                <div style="margin-bottom: 5px;">
                                    • {{ $indicator['name'] ?? 'Indicateur' }}: 
                                    <strong>{{ $indicator['value'] ?? 'N/A' }}</strong>
                                </div>
                            @endforeach
                        @else
                            <span class="empty-cell">Aucun indicateur défini</span>
                        @endif
                    </div>
                </div>

                <!-- Indicateurs de performance -->
                <div class="indicator-box">
                    <div class="indicator-title">⚡ Indicateurs de Performance</div>
                    <div class="indicator-content">
                        @if($contract->performance_indicators && count($contract->performance_indicators) > 0)
                            @foreach($contract->performance_indicators as $indicator)
                                <div style="margin-bottom: 5px;">
                                    • {{ $indicator['name'] ?? 'Indicateur' }}: 
                                    <strong>{{ $indicator['value'] ?? 'N/A' }}</strong>
                                </div>
                            @endforeach
                        @else
                            <span class="empty-cell">Aucun indicateur défini</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- FOOTER -->
        <div class="footer">
            <strong>Contrat d'Interfaces</strong> | Généré le {{ now()->format('d/m/Y à H:i') }}
            <div class="date-info">
                Document protégé - Système de gestion des contrats d'interfaces
            </div>
        </div>
    </div>
</body>
</html>