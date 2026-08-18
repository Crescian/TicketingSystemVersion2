<?php

namespace App\Services\Pdf;

use App\Models\Tickets;
use App\Support\TicketStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use TCPDF;

// Renders the "ICT Service Request Report" — a single-page A4 portrait form
// meant to be printed and physically signed, generated once a ticket is Closed.
// Layout is built with manual SetXY() positioning rather than TCPDF's automatic
// flow, so every section lines up exactly as specified rather than drifting.
class TicketServiceReportPdf
{
    private const MARGIN = 10;
    private const PAGE_WIDTH = 210;
    private const CONTENT_WIDTH = 190; // 210 - 10 - 10
    private const BORDER = 0.3;
    private const GRAY_FILL = [230, 230, 230];

    private TCPDF $pdf;
    private Tickets $ticket;

    public function __construct(Tickets $ticket)
    {
        $this->ticket = $ticket;
        $this->pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    }

    public function output(string $filename): string
    {
        $this->setUp();
        $this->pdf->AddPage();

        $y = self::MARGIN;
        $y = $this->renderHeader($y);
        $y = $this->renderServiceDetails($y);
        $y = $this->renderIssueDescription($y);
        $y = $this->renderWorkDetails($y);
        $y = $this->renderRecommendation($y);
        $this->renderValidation($y);
        $this->renderAttachments();

        return $this->pdf->Output($filename, 'S');
    }

    private function setUp(): void
    {
        $this->pdf->SetCreator('LGICT Support System');
        $this->pdf->SetAuthor('LGICT');
        $this->pdf->SetTitle('ICT Service Request Report - ' . $this->ticket->ticket_number);
        $this->pdf->setPrintHeader(false);
        $this->pdf->setPrintFooter(false);
        $this->pdf->SetMargins(self::MARGIN, self::MARGIN, self::MARGIN);
        $this->pdf->SetAutoPageBreak(false, 0);
        $this->pdf->SetLineWidth(self::BORDER);
        $this->pdf->SetFont('helvetica', '', 8);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetDrawColor(0, 0, 0);
    }

    // ── Header: title/subtitle centered on the page, logo left-aligned beside
    //    them (excluded from the centering so the text is the true page
    //    center, not the center of the logo+text group). ──
    private function renderHeader(float $y): float
    {
        // LGICT.png is a wide horizontal wordmark (~3.7:1), not a square icon —
        // constrain by width (not height).
        $logoPath = public_path('img/LGICT.png');
        $hasLogo = is_file($logoPath);
        $logoWidth = 26;
        $logoHeight = 0;
        if ($hasLogo) {
            $imgSize = @getimagesize($logoPath);
            $logoHeight = $imgSize ? $logoWidth * ($imgSize[1] / $imgSize[0]) : 7;
        }

        $textBlockHeight = 12; // title cell (7) + subtitle cell (5)
        $rowHeight = max($logoHeight, $textBlockHeight);

        if ($hasLogo) {
            $logoY = $y + ($rowHeight - $logoHeight) / 2;
            $this->pdf->Image($logoPath, self::MARGIN, $logoY, $logoWidth, $logoHeight);
        }

        $textY = $y + ($rowHeight - $textBlockHeight) / 2;
        $this->pdf->SetXY(self::MARGIN, $textY);
        $this->pdf->SetFont('helvetica', 'B', 14);
        $this->pdf->Cell(self::CONTENT_WIDTH, 7, 'ICT SERVICE REQUEST REPORT', 0, 1, 'C');

        $this->pdf->SetXY(self::MARGIN, $textY + 7);
        $this->pdf->SetFont('helvetica', '', 9);
        $this->pdf->SetTextColor(110, 110, 110);
        $this->pdf->Cell(self::CONTENT_WIDTH, 5, 'TECHNICAL SUPPORT SPECIALIST', 0, 1, 'C');
        $this->pdf->SetTextColor(0, 0, 0);

        return $y + $rowHeight + 8;
    }

    // ── Section 1: Service Details ──
    private function renderServiceDetails(float $y): float
    {
        $x = self::MARGIN;
        $w = self::CONTENT_WIDTH;

        $y = $this->sectionHeader($x, $y, $w, 'SERVICE DETAILS');

        // Row 1: Date / Start Time / End Time
        $col3 = $w / 3;
        $rowH = 8;
        $this->labelValueCell($x, $y, $col3, $rowH, 'DATE', $this->local($this->ticket->resolved_at, 'M d, Y') ?? '—');
        $this->labelValueCell($x + $col3, $y, $col3, $rowH, 'START TIME', $this->local($this->ticket->started_at, 'h:i A') ?? '—');
        $this->labelValueCell($x + $col3 * 2, $y, $col3, $rowH, 'END TIME', $this->local($this->ticket->resolved_at, 'h:i A') ?? '—');
        $y += $rowH;

        // Row 2: Requested By / BU-Department
        $col2 = $w / 2;
        $buDept = trim(($this->ticket->business_unit ?? '') . ' — ' . ($this->ticket->department ?? ''), ' —');
        $this->labelValueCell($x, $y, $col2, $rowH, 'REQUESTED BY', $this->ticket->user?->name ?? '—');
        $this->labelValueCell($x + $col2, $y, $col2, $rowH, 'BU / DEPARTMENT', $buDept ?: '—');
        $y += $rowH;

        // Row 3: Asset Details / Service Type — the determined value only, not a
        // checklist of every option (previously all three service types were
        // listed with one checked off).
        $this->labelValueCell($x, $y, $col2, $rowH, 'ASSET DETAILS', $this->ticket->asset ?? '—');
        $this->labelValueCell($x + $col2, $y, $col2, $rowH, 'SERVICE TYPE', $this->ticket->service_type ?? '—');
        $y += $rowH;

        // Row 4: Main Category / Level of Request — the determined value only, not
        // a checklist of every possible option (previously all 6 categories / 5
        // levels were listed with one checked off).
        $this->labelValueCell($x, $y, $col2, $rowH, 'MAIN CATEGORY', $this->ticket->mainCategoryLabel() ?? '—');
        $this->labelValueCell($x + $col2, $y, $col2, $rowH, 'LEVEL OF REQUEST', $this->ticket->levelOfRequest() ?? '—');
        $y += $rowH;

        // Row 5: Workload Class / Priority
        $this->labelValueCell($x, $y, $col2, $rowH, 'WORKLOAD CLASS', $this->ticket->workloadClass?->name ?? '—');
        $this->labelValueCell($x + $col2, $y, $col2, $rowH, 'PRIORITY', $this->ticket->ticket_type ?? '—');
        $y += $rowH;

        // Ticket info row: Ticket Number / Status — the determined value only, not
        // a checklist of every status (previously Completed / Pending / Escalated
        // were listed with one or two checked off).
        $this->labelValueCell($x, $y, $col2, $rowH, 'SUPPORT REQUEST NUMBER', $this->ticket->ticket_number);
        $isEscalated = $this->ticket->escalations()->exists();
        $this->labelValueCell($x + $col2, $y, $col2, $rowH, 'STATUS', $isEscalated ? 'Escalated' : 'Completed');
        $y += $rowH;

        return $y;
    }

    // Standard row height — the fixed format. All three columns share one
    // row, so it only grows past standard when the tallest of the three
    // actually needs more room than that.
    private function issueDescriptionBodyHeight(): float
    {
        $w = self::CONTENT_WIDTH;
        $descW = $w / 2;
        $methodW = ($w - $descW) / 2;
        $dateW = $w - $descW - $methodW;

        $this->pdf->SetFont('helvetica', '', 8);
        return max(
            18, // standard row height
            $this->neededHeight($descW - 4, $this->ticket->concern ?? '—'),
            $this->neededHeight($methodW - 4, $this->ticket->method ?? '—'),
            $this->neededHeight($dateW - 4, $this->issueDateReceived()),
        );
    }

    private function issueDateReceived(): string
    {
        return $this->ticket->date_received
            ? \Carbon\Carbon::parse($this->ticket->date_received)->format('M d, Y')
            : '—';
    }

    // ── Issue Description table ──
    private function renderIssueDescription(float $y): float
    {
        $x = self::MARGIN;
        $w = self::CONTENT_WIDTH;
        // descW matches the col2 width used throughout SERVICE DETAILS (w / 2)
        // so this table's first divider lines up with the one above it.
        $descW = $w / 2;
        $methodW = ($w - $descW) / 2;
        $dateW = $w - $descW - $methodW;
        $headerH = 6;

        $this->pdf->SetFillColor(...self::GRAY_FILL);
        $this->pdf->SetFont('helvetica', 'B', 7);
        $this->pdf->SetXY($x, $y);
        $this->pdf->Cell($descW, $headerH, 'DESCRIPTION OF ISSUE / REQUEST', 1, 0, 'L', true);
        $this->pdf->Cell($methodW, $headerH, 'METHOD', 1, 0, 'C', true);
        $this->pdf->Cell($dateW, $headerH, 'DATE RECEIVED', 1, 1, 'C', true);

        $concern = $this->ticket->concern ?? '—';
        $method = $this->ticket->method ?? '—';
        $dateReceived = $this->issueDateReceived();

        $this->pdf->SetFont('helvetica', '', 8);
        $bodyH = $this->issueDescriptionBodyHeight();

        $bodyY = $y + $headerH;
        $this->pdf->Rect($x, $bodyY, $descW, $bodyH, 'D');
        $this->pdf->Rect($x + $descW, $bodyY, $methodW, $bodyH, 'D');
        $this->pdf->Rect($x + $descW + $methodW, $bodyY, $dateW, $bodyH, 'D');

        $this->pdf->SetXY($x + 2, $bodyY + 1.5);
        $this->pdf->MultiCell($descW - 4, $bodyH - 3, $concern, 0, 'L');

        $this->pdf->SetXY($x + $descW + 2, $bodyY + 1.5);
        $this->pdf->MultiCell($methodW - 4, $bodyH - 3, $method, 0, 'L');

        $this->pdf->SetXY($x + $descW + $methodW + 2, $bodyY + 1.5);
        $this->pdf->MultiCell($dateW - 4, $bodyH - 3, $dateReceived, 0, 'L');

        return $bodyY + $bodyH;
    }

    // Standard box height — the fixed format. Both columns share one row, so
    // it only grows past standard when the taller of the two actually needs
    // more room than that.
    private function workDetailsBoxHeight(): float
    {
        $col2 = self::CONTENT_WIDTH / 2;

        $this->pdf->SetFont('helvetica', '', 8);
        return max(
            75, // standard box height
            $this->neededHeight($col2 - 4, $this->ticket->resolution_notes ?? '—', 4),
            $this->neededHeight($col2 - 4, $this->ticket->findings ?: '—', 4),
        );
    }

    // ── Work Details: Action Taken (left) / Findings & Analysis (right) ──
    private function renderWorkDetails(float $y): float
    {
        $x = self::MARGIN;
        $w = self::CONTENT_WIDTH;
        $col2 = $w / 2;
        $headerH = 6;

        $this->pdf->SetFillColor(...self::GRAY_FILL);
        $this->pdf->SetFont('helvetica', 'B', 7);
        $this->pdf->SetXY($x, $y);
        $this->pdf->Cell($col2, $headerH, 'SERVICE DETAILS / ACTION TAKEN', 1, 0, 'C', true);
        $this->pdf->Cell($col2, $headerH, 'FINDINGS & ANALYSIS', 1, 1, 'C', true);

        $actionTaken = $this->ticket->resolution_notes ?? '—';
        $findings = $this->ticket->findings ?: '—';

        $this->pdf->SetFont('helvetica', '', 8);
        $boxH = $this->workDetailsBoxHeight();

        $bodyY = $y + $headerH;
        $this->pdf->Rect($x, $bodyY, $col2, $boxH, 'D');
        $this->pdf->Rect($x + $col2, $bodyY, $col2, $boxH, 'D');

        $this->pdf->SetXY($x + 2, $bodyY + 2);
        $this->pdf->MultiCell($col2 - 4, $boxH - 4, $actionTaken, 0, 'L');

        $this->pdf->SetXY($x + $col2 + 2, $bodyY + 2);
        $this->pdf->MultiCell($col2 - 4, $boxH - 4, $findings, 0, 'L');

        return $bodyY + $boxH;
    }

    // Standard box height — the fixed format. Only grows past standard when
    // the recommendation text actually needs more room than that.
    private function recommendationBoxHeight(): float
    {
        $this->pdf->SetFont('helvetica', '', 8);
        return max(28, $this->neededHeight(self::CONTENT_WIDTH - 4, $this->ticket->recommendation ?: '—', 4));
    }

    // ── Other Observation / Recommendation ──
    private function renderRecommendation(float $y): float
    {
        $x = self::MARGIN;
        $w = self::CONTENT_WIDTH;

        $y = $this->sectionHeader($x, $y, $w, 'OTHER OBSERVATION / RECOMMENDATION');

        $recommendation = $this->ticket->recommendation ?: '—';
        $this->pdf->SetFont('helvetica', '', 8);
        $boxH = $this->recommendationBoxHeight();

        $this->pdf->Rect($x, $y, $w, $boxH, 'D');
        $this->pdf->SetXY($x + 2, $y + 2);
        $this->pdf->MultiCell($w - 4, $boxH - 4, $recommendation, 0, 'L');

        return $y + $boxH;
    }

    // ── Document Validation: 3 signature columns ──
    private function renderValidation(float $y): void
    {
        $x = self::MARGIN;
        $w = self::CONTENT_WIDTH;
        $boxH = 30;
        $col3 = $w / 3;

        $y = $this->sectionHeader($x, $y, $w, 'DOCUMENT VALIDATION');

        $supervisor = $this->ticket->statusHistories()
            ->where('new_status', TicketStatus::APPROVED_SERVICE_REPORT)
            ->latest('changed_at')
            ->first()?->changedBy?->name;

        // resolvedBy (not assignedTo) — correct for every resolver tier, including a
        // Helpdesk direct resolve, where the ticket is never actually assigned to anyone.
        $columns = [
            ['End User Confirmation', $this->ticket->user?->name],
            ['ICT Support / Admin Personnel', $this->ticket->resolvedBy?->name ?? $this->ticket->assignedTo?->name],
            ['ICT Support / Admin Supervisor', $supervisor],
        ];

        foreach ($columns as $i => [$title, $name]) {
            $colX = $x + $col3 * $i;
            $this->pdf->Rect($colX, $y, $col3, $boxH, 'D');

            $this->pdf->SetFont('helvetica', 'B', 7);
            $this->pdf->SetXY($colX + 2, $y + 2);
            $this->pdf->MultiCell($col3 - 4, 6, $title, 0, 'C');

            $this->pdf->SetFont('helvetica', '', 8);
            $this->pdf->SetXY($colX + 4, $y + 16);
            $this->pdf->Cell($col3 - 8, 4, $name ?: '', 0, 0, 'C');
            $this->pdf->Line($colX + 4, $y + 20, $colX + $col3 - 4, $y + 20);

            $this->pdf->SetFont('helvetica', '', 6);
            $this->pdf->SetXY($colX + 4, $y + 20.5);
            $this->pdf->Cell($col3 - 8, 3, 'Signature over Printed Name', 0, 0, 'C');

            $this->pdf->SetFont('helvetica', '', 7);
            $this->pdf->SetXY($colX + 4, $y + 25);
            $dateSigned = $i === 0
                ? $this->local($this->ticket->updated_at, 'M d, Y') // requestor's ack timestamp is closest available
                : $this->local($this->ticket->resolved_at, 'M d, Y');
            $this->pdf->Cell($col3 - 8, 4, 'Date Signed: ' . ($dateSigned ?? ''), 0, 0, 'L');
        }
    }

    // ── Attachments — the resolver's own evidence photos, on a dedicated page so
    //    they always have room to be a legible size regardless of how many there
    //    are. Skipped entirely if there's nothing to show, rather than printing an
    //    empty page. Non-image files can't be shown as thumbnails, so they're
    //    listed by name underneath instead of silently dropped. ──
    private function renderAttachments(): void
    {
        $attachments = $this->ticket->attachments()
            ->where('uploaded_by', $this->ticket->resolved_by)
            ->get();

        if ($attachments->isEmpty()) {
            return;
        }

        $images = $attachments->filter(fn($a) => str_starts_with($a->mime_type ?? '', 'image/'))->values();
        $others = $attachments->reject(fn($a) => str_starts_with($a->mime_type ?? '', 'image/'))->values();

        $x = self::MARGIN;
        $w = self::CONTENT_WIDTH;

        $this->pdf->AddPage();
        $y = self::MARGIN;

        $this->pdf->SetXY($x, $y);
        $this->pdf->SetFont('helvetica', 'B', 12);
        $this->pdf->Cell($w, 6, 'ATTACHMENTS — ' . $this->ticket->ticket_number, 0, 1, 'L');

        $this->pdf->SetFont('helvetica', '', 9);
        $this->pdf->SetTextColor(110, 110, 110);
        $this->pdf->SetXY($x, $y + 6);
        $this->pdf->Cell($w, 5, 'Uploaded by ' . ($this->ticket->resolvedBy?->name ?? 'the ICT Support Specialist'), 0, 1, 'L');
        $this->pdf->SetTextColor(0, 0, 0);

        $y = $this->sectionHeader($x, $y + 14, $w, 'SUPPORTING IMAGES');
        $y += 3;

        if ($images->isEmpty()) {
            $this->pdf->SetFont('helvetica', '', 8);
            $this->pdf->SetXY($x, $y);
            $this->pdf->Cell($w, 5, 'No image attachments were uploaded.', 0, 1, 'L');
            $y += 7;
        } else {
            $y = $this->renderImageGrid($x, $y, $w, $images);
        }

        if ($others->isNotEmpty()) {
            $y += 4;
            $this->pdf->SetFont('helvetica', 'B', 8);
            $this->pdf->SetXY($x, $y);
            $this->pdf->Cell($w, 5, 'Other Supporting Files', 0, 1, 'L');
            $y += 5;

            $this->pdf->SetFont('helvetica', '', 8);
            foreach ($others as $other) {
                $this->pdf->SetXY($x + 3, $y);
                $this->pdf->Cell($w - 3, 4, '- ' . $other->original_name . ' (' . $other->humanSize() . ')', 0, 1, 'L');
                $y += 4.5;
            }
        }
    }

    // 3-column thumbnail grid, paginating onto additional pages once a page fills up.
    private function renderImageGrid(float $x, float $y, float $w, $images): float
    {
        $cols = 3;
        $rowsPerPage = 4;
        $gap = 4;
        $cellW = ($w - ($cols - 1) * $gap) / $cols;
        $imgBoxH = 45;
        $cellH = $imgBoxH + 8;

        $col = 0;
        $row = 0;
        $rowY = $y;

        foreach ($images as $i => $image) {
            $cellX = $x + $col * ($cellW + $gap);
            $this->pdf->Rect($cellX, $rowY, $cellW, $cellH, 'D');
            $this->drawThumbnail($image, $cellX, $rowY, $cellW, $imgBoxH);

            $this->pdf->SetFont('helvetica', '', 6.5);
            $this->pdf->SetXY($cellX + 1, $rowY + $imgBoxH + 2);
            $this->pdf->Cell($cellW - 2, 3, $this->truncateFilename($image->original_name), 0, 0, 'C');

            $col++;
            if ($col >= $cols) {
                $col = 0;
                $row++;
                $rowY += $cellH + $gap;

                $isLast = $i === $images->count() - 1;
                if ($row >= $rowsPerPage && !$isLast) {
                    $this->pdf->AddPage();
                    $rowY = self::MARGIN;
                    $row = 0;
                }
            }
        }

        return $col > 0 ? $rowY + $cellH + $gap : $rowY;
    }

    private function drawThumbnail($image, float $cellX, float $cellY, float $cellW, float $boxH): void
    {
        if (!Storage::disk('local')->exists($image->stored_path)) {
            $this->pdf->SetFont('helvetica', '', 7);
            $this->pdf->SetXY($cellX, $cellY + $boxH / 2 - 2);
            $this->pdf->Cell($cellW, 4, '(file unavailable)', 0, 0, 'C');
            return;
        }

        $path = Storage::disk('local')->path($image->stored_path);
        $size = @getimagesize($path);
        if (!$size) {
            return;
        }

        [$imgW, $imgH] = $size;
        $scale = min(($cellW - 4) / $imgW, ($boxH - 4) / $imgH);
        $drawW = $imgW * $scale;
        $drawH = $imgH * $scale;

        $this->pdf->Image(
            $path,
            $cellX + ($cellW - $drawW) / 2,
            $cellY + 2 + ($boxH - 4 - $drawH) / 2,
            $drawW,
            $drawH
        );
    }

    private function truncateFilename(string $name, int $max = 26): string
    {
        return mb_strlen($name) > $max ? mb_substr($name, 0, $max - 1) . '…' : $name;
    }

    // ── Shared helpers ──

    // Timestamps are stored/cast in UTC (config('app.timezone')) — this is a printed
    // form read by local office staff, so display everything in local time.
    private function local(?Carbon $dt, string $format): ?string
    {
        return $dt?->copy()->setTimezone('Asia/Manila')->format($format);
    }

    // How tall $text needs to wrap at $width under the current font, plus
    // top/bottom padding — callers max() this against the section's standard
    // fixed height, so a box only grows past the standard format when
    // content actually needs more room than it allows.
    private function neededHeight(float $width, string $text, float $padding = 3): float
    {
        return $this->pdf->getStringHeight($width, $text) + $padding;
    }

    private function sectionHeader(float $x, float $y, float $w, string $label): float
    {
        $h = 6;
        $this->pdf->SetFillColor(...self::GRAY_FILL);
        $this->pdf->SetFont('helvetica', 'B', 9);
        $this->pdf->SetXY($x, $y);
        $this->pdf->Cell($w, $h, $label, 1, 1, 'L', true);
        return $y + $h;
    }

    private function labelValueCell(float $x, float $y, float $w, float $h, string $label, string $value): void
    {
        $this->pdf->Rect($x, $y, $w, $h, 'D');

        // Center the label+value block vertically so the gap above the label
        // matches the gap below the value, instead of the fixed 1.2 / 4.5
        // offsets that left almost no room under the value.
        $labelH = 3;
        $valueH = 3.3;
        $innerGap = 0.3;
        $padding = ($h - ($labelH + $innerGap + $valueH)) / 2;

        $this->pdf->SetFont('helvetica', '', 6.5);
        $this->pdf->SetTextColor(120, 120, 120);
        $this->pdf->SetXY($x + 2, $y + $padding);
        $this->pdf->Cell($w - 4, $labelH, $label, 0, 0, 'L');
        $this->pdf->SetTextColor(0, 0, 0);

        $this->pdf->SetFont('helvetica', 'B', 8);
        $this->pdf->SetXY($x + 2, $y + $padding + $labelH + $innerGap);
        $this->pdf->Cell($w - 4, $valueH, $value, 0, 0, 'L');
    }

}
