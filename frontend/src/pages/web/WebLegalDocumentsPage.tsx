import { useState, useEffect } from 'react';
import { legalDocumentsAPI } from '@/api';
import { Loader2 } from 'lucide-react';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';

interface Document {
  id: number;
  type: string;
  title: string;
  content: string | null;
  url: string | null;
}

const DOCUMENT_TYPE_LABELS: Record<string, string> = {
  privacy_policy: 'Политика конфиденциальности',
  offer: 'Публичная оферта',
  contacts: 'Контакты',
};

export function WebLegalDocumentsPage() {
  const [documents, setDocuments] = useState<Document[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [selectedDocument, setSelectedDocument] = useState<Document | null>(null);
  const [isDialogOpen, setIsDialogOpen] = useState(false);

  useEffect(() => {
    const load = async () => {
      try {
        const data = await legalDocumentsAPI.getAll();
        setDocuments(data || []);
      } catch (err: unknown) {
        setError(err instanceof Error ? err.message : 'Ошибка загрузки');
      } finally {
        setLoading(false);
      }
    };
    load();
  }, []);

  const handleDocumentClick = (doc: Document) => {
    if (doc.url) {
      window.open(doc.url, '_blank');
    } else if (doc.content) {
      setSelectedDocument(doc);
      setIsDialogOpen(true);
    }
  };

  if (loading) {
    return (
      <div className="flex flex-col items-center justify-center py-20">
          <Loader2 className="h-8 w-8 animate-spin text-primary" />
          <p className="mt-4 text-muted-foreground">Загрузка...</p>
        </div>
    );
  }

  if (error) {
    return (
      <div className="container mx-auto px-4 py-16 text-center">
          <p className="text-destructive">{error}</p>
        </div>
    );
  }

  return (
    <>
      <div className="container mx-auto px-4 py-12 lg:px-8">
        <h1 className="mb-8 text-2xl font-bold md:text-3xl">Документы</h1>
        <div className="space-y-2">
          {documents.map((doc) => (
            <button
              key={doc.id}
              onClick={() => handleDocumentClick(doc)}
              className="flex w-full items-center justify-between rounded-lg border border-border bg-card px-4 py-4 text-left transition-colors hover:border-primary/30 hover:bg-muted/50"
            >
              <span>
                {DOCUMENT_TYPE_LABELS[doc.type] || doc.title}
              </span>
              <span className="text-muted-foreground">→</span>
            </button>
          ))}
        </div>
      </div>

      <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>
              {selectedDocument &&
                (DOCUMENT_TYPE_LABELS[selectedDocument.type] ||
                  selectedDocument.title)}
            </DialogTitle>
          </DialogHeader>
          {selectedDocument?.content && (
            <div className="prose prose-sm max-h-[60vh] overflow-y-auto dark:prose-invert">
              <pre className="whitespace-pre-wrap font-sans text-sm">
                {selectedDocument.content}
              </pre>
            </div>
          )}
        </DialogContent>
      </Dialog>
    </>
  );
}
