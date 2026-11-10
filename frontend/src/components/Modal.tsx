import { useEffect, useRef, type ReactNode } from 'react';
import { X } from 'lucide-react';
import { Button } from './Button';

interface ModalProps {
  open?: boolean;
  isOpen?: boolean;
  title: string;
  description?: string;
  size?: 'sm' | 'md' | 'lg' | 'xl';
  onClose: () => void;
  children: ReactNode;
  footer?: ReactNode;
}

export function Modal({ open, isOpen, title, description, size = 'md', onClose, children, footer }: ModalProps) {
  const isVisible = open ?? isOpen ?? false;
  const dialogRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (!isVisible) return;
    const onKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') onClose();
    };
    document.addEventListener('keydown', onKey);
    dialogRef.current?.focus();
    return () => document.removeEventListener('keydown', onKey);
  }, [isVisible, onClose]);

  if (!isVisible) return null;

  return (
    <div className="modal-backdrop" onMouseDown={(e) => e.target === e.currentTarget && onClose()}>
      <div
        className={`modal modal--${size}`}
        role="dialog"
        aria-modal="true"
        aria-labelledby="modal-title"
        tabIndex={-1}
        ref={dialogRef}
      >
        <div className="modal__header">
          <div className="modal__header-content">
            <h2 id="modal-title" className="modal__title">
              {title}
            </h2>
            {description && <p className="modal__desc">{description}</p>}
          </div>
          <Button
            variant="ghost"
            iconOnly
            aria-label="Tutup"
            className="modal__close"
            icon={<X size={18} />}
            onClick={onClose}
          />
        </div>
        <div className="modal__body">{children}</div>
        {footer && <div className="modal__footer">{footer}</div>}
      </div>
    </div>
  );
}

interface ConfirmationDialogProps {
  open?: boolean;
  isOpen?: boolean;
  title: string;
  /** Must explain the consequence of the action (agent_docs/10_UI_UX_GUIDELINES.md). */
  message?: ReactNode;
  description?: ReactNode;
  confirmLabel?: string;
  confirmText?: string;
  danger?: boolean;
  confirmVariant?: 'primary' | 'secondary' | 'danger' | 'ghost' | string;
  variant?: 'primary' | 'secondary' | 'danger' | 'ghost' | string;
  loading?: boolean;
  onConfirm: () => void;
  onCancel: () => void;
}

export function ConfirmationDialog({
  open,
  isOpen,
  title,
  message,
  description,
  confirmLabel,
  confirmText,
  danger,
  confirmVariant,
  variant,
  loading = false,
  onConfirm,
  onCancel,
}: ConfirmationDialogProps) {
  const isVisible = open ?? isOpen ?? false;
  const content = message ?? description;
  const label = confirmText ?? confirmLabel ?? 'Konfirmasi';
  const isDanger = danger ?? (confirmVariant === 'danger' || variant === 'danger');

  return (
    <Modal
      open={isVisible}
      title={title}
      size="sm"
      onClose={onCancel}
      footer={
        <>
          <Button onClick={onCancel} disabled={loading}>
            Batal
          </Button>
          <Button variant={isDanger ? 'danger' : 'primary'} onClick={onConfirm} loading={loading}>
            {label}
          </Button>
        </>
      }
    >
      <div className="confirmation-dialog__content">{content}</div>
    </Modal>
  );
}
