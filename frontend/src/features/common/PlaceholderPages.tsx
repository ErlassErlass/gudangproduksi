import { Construction } from 'lucide-react';
import { useLocation } from 'react-router-dom';
import { PageHeader } from '@/components/PageHeader';
import { EmptyState } from '@/components/States';
import { findNavItem } from '@/routes/navigation';

/** Placeholder for modules scheduled in later roadmap phases. Shows no fabricated data. */
export function ModulePlaceholderPage() {
  const location = useLocation();
  const item = findNavItem(location.pathname);

  return (
    <>
      <PageHeader eyebrow={`Fase ${item?.phase ?? '-'}`} title={item?.label ?? 'Modul'} />
      <section className="card">
        <EmptyState
          icon={<Construction size={26} />}
          title="Modul belum tersedia"
          description={`Modul ini dijadwalkan pada Fase ${item?.phase ?? '-'} roadmap implementasi dan akan diaktifkan setelah fase sebelumnya lulus gate pengujian.`}
        />
      </section>
    </>
  );
}

export function NotFoundPage() {
  return (
    <section className="card">
      <EmptyState title="Halaman tidak ditemukan" description="Periksa kembali alamat yang Anda tuju." />
    </section>
  );
}
