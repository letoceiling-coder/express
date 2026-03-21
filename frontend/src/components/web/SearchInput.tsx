import { Search } from 'lucide-react';

interface SearchInputProps {
  value: string;
  onChange: (v: string) => void;
  placeholder?: string;
  className?: string;
}

/** Premium search input - EXACT as MiniApp: rounded-xl, icon inside, same padding */
export function SearchInput({
  value,
  onChange,
  placeholder = 'Поиск товаров...',
  className = '',
}: SearchInputProps) {
  return (
    <div className={`relative w-full ${className}`}>
      <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground w-4 h-4" />
      <input
        type="text"
        placeholder={placeholder}
        value={value}
        onChange={(e) => onChange(e.target.value)}
        className="w-full pl-10 pr-4 py-3 rounded-xl border border-border bg-muted/50 focus:bg-background focus:ring-2 focus:ring-primary outline-none transition placeholder:text-muted-foreground"
      />
    </div>
  );
}
