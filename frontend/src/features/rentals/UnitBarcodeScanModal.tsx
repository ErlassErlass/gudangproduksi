import { BarcodeScanModal } from '@/features/catalog/BarcodeScanModal';

interface UnitBarcodeScanModalProps {
  isOpen: boolean;
  onClose: () => void;
  initialUnitCode?: string;
}

export function UnitBarcodeScanModal({
  isOpen,
  onClose,
  initialUnitCode,
}: UnitBarcodeScanModalProps) {
  return (
    <BarcodeScanModal
      isOpen={isOpen}
      onClose={onClose}
      initialBarcode={initialUnitCode}
    />
  );
}
