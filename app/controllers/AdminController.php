<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/Report.php';

final class AdminController extends Controller
{
    public function dashboard(array $params = []): string
    {
        $report = new Report();
        $dashboard = [
            'metrics' => $report->summaryMetrics(),
            'recentTransactions' => $report->recentTransactions(7),
            'overdueTransactions' => $report->overdueTransactions(6),
            'categoryBreakdown' => $report->categoryBreakdown(6),
            'circulationTrend' => $report->circulationTrend(7),
        ];

        return $this->render('admin/dashboard', [
            'pageTitle' => 'Admin dashboard',
            'pageDescription' => 'AuraLib collection, circulation, and overdue intelligence.',
            'dashboard' => $dashboard,
            'overdueCount' => (int) $dashboard['metrics']['overdue_loans'],
            'flashMessages' => $this->pullFlashMessages(),
            'pageScripts' => ['js/dashboard.js'],
        ]);
    }
}
