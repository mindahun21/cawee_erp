<?php

namespace App\Services\Procurement;

/**
 * JSI Procurement Threshold Constants
 *
 * Encodes JSI's four official procurement tiers (ETB-denominated).
 * All resources reference this class so thresholds are changed in one place.
 *
 * Tiers per JSI letter (March 2026):
 *  1. Micro Purchase          < ETB 77,000
 *  2. Simplified/Competitive  ETB 77,000 – 1,539,846
 *  3. RFQ / RFP               ETB 1,540,000 – 38,499,846
 *  4. Open Competition        > ETB 38,500,000
 */
class JsiThresholds
{
    // ── Threshold boundaries (ETB) ────────────────────────────────────
    public const MICRO_MAX           = 76_999;          // < 77,000
    public const SIMPLIFIED_MIN      = 77_000;
    public const SIMPLIFIED_MAX      = 1_539_846;
    public const RFQ_MIN             = 1_540_000;
    public const RFQ_MAX             = 38_499_846;
    public const OPEN_MIN            = 38_500_000;      // > 38,500,000

    // ── Tier identifiers ─────────────────────────────────────────────
    public const TIER_MICRO       = 'micro';
    public const TIER_SIMPLIFIED  = 'simplified';
    public const TIER_RFQ         = 'rfq_rfp';
    public const TIER_OPEN        = 'open';

    // ── Display metadata keyed by tier ───────────────────────────────
    public static function tiers(): array
    {
        return [
            self::TIER_MICRO => [
                'label'         => 'Micro Purchase',
                'short'         => 'Micro',
                'range'         => '< ETB 77,000',
                'color'         => '#10b981',          // emerald
                'badge_bg'      => '#d1fae5',
                'badge_text'    => '#065f46',
                'dark_bg'       => '#064e3b',
                'dark_text'     => '#6ee7b7',
                'icon'          => 'heroicon-o-shopping-bag',
                'quotations'    => 1,
                'method_label'  => 'Direct Purchase',
                'requirements'  => 'Single quotation · No competition required · Direct procurement with justification',
                'steps'         => ['Purchase Request', 'PR Approval', 'Direct Quote', 'Purchase Order', 'GRN', 'Invoice', 'Payment'],
            ],
            self::TIER_SIMPLIFIED => [
                'label'         => 'Simplified / Competitive',
                'short'         => 'Simplified',
                'range'         => 'ETB 77,000 – 1,539,846',
                'color'         => '#3b82f6',          // blue
                'badge_bg'      => '#dbeafe',
                'badge_text'    => '#1e3a5f',
                'dark_bg'       => '#1e3a5f',
                'dark_text'     => '#93c5fd',
                'icon'          => 'heroicon-o-document-duplicate',
                'quotations'    => 3,
                'method_label'  => 'Competitive RFQ (3 quotes)',
                'requirements'  => 'Minimum 3 written/electronic quotations · Vendor comparison · Price analysis required',
                'steps'         => ['Purchase Request', 'PR Approval', 'RFQ to Suppliers (min 3)', 'Quote Comparison / Price Analysis', 'Purchase Order', 'GRN', 'Invoice', 'Payment'],
            ],
            self::TIER_RFQ => [
                'label'         => 'RFQ / RFP Based',
                'short'         => 'RFQ/RFP',
                'range'         => 'ETB 1,540,000 – 38,499,846',
                'color'         => '#f59e0b',          // amber
                'badge_bg'      => '#fef3c7',
                'badge_text'    => '#78350f',
                'dark_bg'       => '#78350f',
                'dark_text'     => '#fcd34d',
                'icon'          => 'heroicon-o-document-magnifying-glass',
                'quotations'    => 3,
                'method_label'  => 'Formal RFQ / RFP',
                'requirements'  => 'Issue formal RFQ or RFP · Minimum 3 bids · Technical & Financial evaluation · Bid documentation',
                'steps'         => ['Purchase Request', 'PR Approval', 'Tender / RFQ Creation', 'Bid Submission (min 3)', 'Bid Evaluation', 'Award', 'Purchase Order', 'GRN', 'Invoice', 'Payment'],
            ],
            self::TIER_OPEN => [
                'label'         => 'Open Competition',
                'short'         => 'Open Tender',
                'range'         => '> ETB 38,500,000',
                'color'         => '#ef4444',          // red
                'badge_bg'      => '#fee2e2',
                'badge_text'    => '#7f1d1d',
                'dark_bg'       => '#7f1d1d',
                'dark_text'     => '#fca5a5',
                'icon'          => 'heroicon-o-globe-alt',
                'quotations'    => 0,
                'method_label'  => 'Open Competitive Tender',
                'requirements'  => 'Full & open competition · Published solicitation · Advertisement · Bid submission & opening · Complete evaluation workflow',
                'steps'         => ['Purchase Request', 'PR Approval', 'Tender Publication', 'Public Advertisement', 'Bid Submission Period', 'Bid Opening', 'Technical Evaluation', 'Financial Evaluation', 'Award Decision', 'Purchase Order', 'GRN', 'Invoice', 'Payment'],
            ],
        ];
    }

    /**
     * Determine the JSI tier for a given ETB amount.
     */
    public static function tierFor(float $amount): string
    {
        if ($amount < self::SIMPLIFIED_MIN)  return self::TIER_MICRO;
        if ($amount <= self::SIMPLIFIED_MAX) return self::TIER_SIMPLIFIED;
        if ($amount <= self::RFQ_MAX)        return self::TIER_RFQ;
        return self::TIER_OPEN;
    }

    /**
     * Returns the display metadata for a given ETB amount.
     */
    public static function metaFor(float $amount): array
    {
        return static::tiers()[static::tierFor($amount)];
    }

    /**
     * Recommended procurement method name for use in dropdowns.
     */
    public static function recommendedMethodFor(float $amount): string
    {
        return match (static::tierFor($amount)) {
            self::TIER_MICRO      => 'Micro Purchase',
            self::TIER_SIMPLIFIED => 'Simplified Procurement',
            self::TIER_RFQ        => 'RFQ/RFP',
            self::TIER_OPEN       => 'Open Competition',
        };
    }

    /**
     * Minimum number of quotations required for an amount.
     */
    public static function quotationsRequired(float $amount): int
    {
        return static::tiers()[static::tierFor($amount)]['quotations'];
    }

    /**
     * Advisory HTML block shown inline on the Requisition form.
     * Redesigned for JSI demo — professional spec-table style.
     */
    public static function advisoryHtml(float $amount): string
    {
        if ($amount <= 0) {
            return '<div style="padding:12px 16px;border-radius:8px;border:1px dashed #e2e8f0;text-align:center;">
                        <p style="color:#94a3b8;font-size:.8rem;margin:0;">Enter an estimated total above to see the applicable JSI procurement method.</p>
                    </div>';
        }

        $tierKey  = static::tierFor($amount);
        $tier     = static::tiers()[$tierKey];
        $color    = $tier['color'];
        $label    = $tier['label'];
        $range    = $tier['range'];
        $method   = $tier['method_label'];
        $reqs     = $tier['requirements'];
        $formatted = 'ETB ' . number_format($amount, 2);

        $quoteTxt = match(true) {
            $tier['quotations'] === 0 => 'Full open competitive tender (public advertisement required)',
            $tier['quotations'] === 1 => '1 quotation (single-source justification on file)',
            default                   => "{$tier['quotations']} written quotations minimum",
        };

        $nextStep = match($tierKey) {
            self::TIER_MICRO      => 'Obtain 1 supplier quotation → raise Purchase Order → route for approval',
            self::TIER_SIMPLIFIED => 'Issue RFQ to ≥ 3 suppliers → collect quotes → complete price analysis form → raise PO',
            self::TIER_RFQ        => 'Prepare formal RFP document → publish to pre-qualified suppliers → receive ≥ 3 bids → evaluate',
            self::TIER_OPEN       => 'Procure Evaluation Committee approval → publish public tender → advertise → collect bids → evaluate',
        };

        $tierNum = match($tierKey) {
            self::TIER_MICRO      => '① Micro',
            self::TIER_SIMPLIFIED => '② Simplified',
            self::TIER_RFQ        => '③ RFQ / RFP',
            self::TIER_OPEN       => '④ Open',
        };

        return <<<HTML
        <style>
            .jsi-adv { border:1px solid {$color}30; border-left:3px solid {$color}; border-radius:0 7px 7px 0; overflow:hidden; }
            .jsi-adv-hd { display:flex; align-items:center; justify-content:space-between; padding:10px 14px; background:{$color}10; border-bottom:1px solid {$color}20; }
            .jsi-adv-tier { font-size:.65rem; font-weight:800; text-transform:uppercase; letter-spacing:.08em; padding:3px 10px; border-radius:4px; background:{$color}; color:#fff; }
            .jsi-adv-range { font-size:.72rem; font-weight:600; color:{$color}; }
            .jsi-adv-body { padding:0; }
            .jsi-adv-row { display:grid; grid-template-columns:140px 1fr; gap:0; border-bottom:1px solid {$color}12; }
            .jsi-adv-row:last-child { border-bottom:none; }
            .jsi-adv-key { font-size:.7rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#94a3b8; padding:8px 14px; white-space:nowrap; background:{$color}06; border-right:1px solid {$color}12; display:flex; align-items:center; }
            .jsi-adv-val { font-size:.78rem; color:#334155; padding:8px 14px; display:flex; align-items:center; line-height:1.5; }
            .jsi-adv-next { padding:9px 14px; background:{$color}08; border-top:1px solid {$color}15; display:flex; align-items:flex-start; gap:8px; }
            .jsi-adv-next-lbl { font-size:.62rem; font-weight:800; text-transform:uppercase; letter-spacing:.07em; color:{$color}; white-space:nowrap; padding-top:2px; }
            .jsi-adv-next-txt { font-size:.75rem; color:#475569; line-height:1.5; }
        </style>
        <div class="jsi-adv">
            <div class="jsi-adv-hd">
                <div style="display:flex;align-items:center;gap:8px;">
                    <span class="jsi-adv-tier">{$tierNum}</span>
                    <span style="font-size:.85rem;font-weight:700;color:#0f172a;">{$label}</span>
                </div>
                <span class="jsi-adv-range">{$range}</span>
            </div>
            <div class="jsi-adv-body">
                <div class="jsi-adv-row">
                    <div class="jsi-adv-key">Estimated Total</div>
                    <div class="jsi-adv-val"><strong style="color:{$color};">{$formatted}</strong></div>
                </div>
                <div class="jsi-adv-row">
                    <div class="jsi-adv-key">Method</div>
                    <div class="jsi-adv-val">{$method}</div>
                </div>
                <div class="jsi-adv-row">
                    <div class="jsi-adv-key">Quotations</div>
                    <div class="jsi-adv-val">{$quoteTxt}</div>
                </div>
                <div class="jsi-adv-row">
                    <div class="jsi-adv-key">Requirements</div>
                    <div class="jsi-adv-val">{$reqs}</div>
                </div>
            </div>
            <div class="jsi-adv-next">
                <span class="jsi-adv-next-lbl">Next Step</span>
                <span class="jsi-adv-next-txt">{$nextStep}</span>
            </div>
        </div>
        HTML;
    }
}
