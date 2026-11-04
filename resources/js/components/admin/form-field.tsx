import type { ReactNode } from 'react';
import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';

export function FormField({
    label,
    htmlFor,
    error,
    hint,
    children,
    className,
}: {
    label: string;
    htmlFor?: string;
    error?: string;
    hint?: string;
    children: ReactNode;
    className?: string;
}) {
    return (
        <div className={`grid gap-2 ${className ?? ''}`}>
            <Label htmlFor={htmlFor}>{label}</Label>
            {children}
            {hint && <p className="text-xs text-muted-foreground">{hint}</p>}
            <InputError message={error} />
        </div>
    );
}

/** Native select styled like the shadcn inputs; simpler than Radix for plain forms. */
export function NativeSelect({
    className,
    ...props
}: React.ComponentProps<'select'>) {
    return (
        <select
            {...props}
            className={`h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 ${className ?? ''}`}
        />
    );
}

export function CheckboxField({
    label,
    checked,
    onChange,
    hint,
}: {
    label: string;
    checked: boolean;
    onChange: (checked: boolean) => void;
    hint?: string;
}) {
    return (
        <label className="flex cursor-pointer items-start gap-2 text-sm">
            <input
                type="checkbox"
                checked={checked}
                onChange={(event) => onChange(event.target.checked)}
                className="mt-0.5 size-4 rounded border-input"
            />
            <span>
                {label}
                {hint && <span className="block text-xs text-muted-foreground">{hint}</span>}
            </span>
        </label>
    );
}
