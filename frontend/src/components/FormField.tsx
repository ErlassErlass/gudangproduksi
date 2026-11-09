import type { ReactNode } from 'react';

interface FormFieldProps {
  label: string;
  htmlFor: string;
  required?: boolean;
  hint?: string;
  description?: string;
  error?: string;
  children: ReactNode;
}

export function FormField({ label, htmlFor, required, hint, description, error, children }: FormFieldProps) {
  const effectiveHint = hint ?? description;
  return (
    <div className="field">
      <label className="field__label" htmlFor={htmlFor}>
        {label}
        {required && (
          <span className="field__required" aria-hidden="true">
            *
          </span>
        )}
      </label>
      {children}
      {effectiveHint && !error && <span className="field__hint">{effectiveHint}</span>}
      {error && (
        <span className="field__error" role="alert">
          {error}
        </span>
      )}
    </div>
  );
}
