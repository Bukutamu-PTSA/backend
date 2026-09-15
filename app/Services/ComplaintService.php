<?php

namespace App\Services;

use App\Models\Complaint;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;

class ComplaintService
{
    /**
     * Jumlah hari SLA tindak lanjut berdasarkan prioritas aduan.
     *
     * @var array<string, int>
     */
    private const SLA_DAYS = [
        'critical' => 1,
        'high' => 3,
        'medium' => 7,
        'low' => 14,
    ];

    /**
     * Buat tiket aduan baru lengkap dengan nomor tiket unik,
     * tanggal batas (SLA) otomatis, dan catatan riwayat status awal.
     *
     * @param  array<string, mixed>  $data
     */
    public function createComplaint(array $data): Complaint
    {
        $priority = $data['priority'] ?? 'medium';
        $complaintDate = isset($data['complaint_date'])
            ? Carbon::parse($data['complaint_date'])
            : now();

        // Hitung tanggal batas hanya bila tidak dikirim eksplisit.
        if (empty($data['deadline'])) {
            $data['deadline'] = $this->calculateDeadline($priority, $complaintDate);
        }

        $data['priority'] = $priority;
        $data['status'] = $data['status'] ?? 'submitted';
        $data['complaint_date'] = $complaintDate;

        $complaint = $this->createWithUniqueTicket($data);

        $this->recordStatusChange($complaint, null, $complaint->status, 'Aduan dibuat');

        return $complaint;
    }

    /**
     * Ubah status aduan dan catat riwayat perubahannya.
     */
    public function changeStatus(Complaint $complaint, string $newStatus, ?string $notes = null): Complaint
    {
        $oldStatus = $complaint->status;

        $attributes = ['status' => $newStatus];

        if ($newStatus === 'closed' && $complaint->closed_at === null) {
            $attributes['closed_at'] = now();
        }

        $complaint->update($attributes);

        $this->recordStatusChange($complaint, $oldStatus, $newStatus, $notes);

        return $complaint;
    }

    /**
     * Hitung tanggal batas tindak lanjut berdasarkan prioritas.
     */
    public function calculateDeadline(string $priority, CarbonImmutable|Carbon|string|null $from = null): Carbon
    {
        $days = self::SLA_DAYS[$priority] ?? self::SLA_DAYS['medium'];
        $base = $from ? Carbon::parse($from) : now();

        return $base->copy()->addDays($days);
    }

    /**
     * Nomor tiket dengan format: 3 digit nomor urut (reset harian)
     * + 2 digit tanggal + 2 digit bulan + 2 digit tahun.
     * Contoh: 001150926 = urutan ke-1 pada 15 September 2026.
     */
    public function nextTicketNumber(?Carbon $date = null): string
    {
        $date = $date ? Carbon::parse($date) : now();
        $dateCode = $date->format('dmy');

        // Ambil nomor urut terakhir untuk tanggal ini berdasarkan tiket yang
        // sudah ada (akhiran = dateCode), lalu tambah satu. Cara ini membuat
        // nomor tetap berlanjut walau ada aduan yang dihapus di hari yang sama.
        $lastSequence = (int) Complaint::query()
            ->withoutGlobalScopes()
            ->whereDate('complaint_date', $date->toDateString())
            ->where('ticket_number', 'like', '%'.$dateCode)
            ->selectRaw('MAX(CAST(LEFT(ticket_number, 3) AS INTEGER)) AS seq')
            ->value('seq');

        return sprintf('%03d%s', $lastSequence + 1, $dateCode);
    }

    /**
     * Catat riwayat perubahan status pada tabel updates.
     */
    private function recordStatusChange(Complaint $complaint, ?string $oldStatus, string $newStatus, ?string $notes): void
    {
        $complaint->updates()->create([
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'notes' => $notes,
        ]);
    }

    /**
     * Simpan aduan dengan mekanisme retry apabila terjadi tabrakan nomor tiket.
     *
     * @param  array<string, mixed>  $data
     */
    private function createWithUniqueTicket(array $data, int $retries = 5): Complaint
    {
        $date = isset($data['complaint_date']) ? Carbon::parse($data['complaint_date']) : now();

        for ($attempt = 1; $attempt <= $retries; $attempt++) {
            try {
                // Hitung ulang nomor urut tiap percobaan agar bertambah bila
                // terjadi tabrakan akibat aduan lain yang masuk bersamaan.
                return Complaint::create([...$data, 'ticket_number' => $this->nextTicketNumber($date)]);
            } catch (QueryException $e) {
                if ($attempt === $retries) {
                    throw $e;
                }
            }
        }

        throw new \RuntimeException('Gagal membuat nomor tiket aduan yang unik');
    }
}
