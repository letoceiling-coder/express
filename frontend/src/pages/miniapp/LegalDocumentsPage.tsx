import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { MiniAppHeader } from '@/components/miniapp/MiniAppHeader';
import { BottomNavigation } from '@/components/miniapp/BottomNavigation';
import { Loader2, FileText, ChevronRight, ExternalLink } from 'lucide-react';
import { legalDocumentsAPI } from '@/api';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';

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

export function LegalDocumentsPage() {
  const navigate = useNavigate();
  const [documents, setDocuments] = useState<Document[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [selectedDocument, setSelectedDocument] = useState<Document | null>(null);
  const [isDialogOpen, setIsDialogOpen] = useState(false);

  useEffect(() => {
    loadDocuments();
  }, []);

  const loadDocuments = async () => {
    try {
      setLoading(true);
      setError(null);
      const data = await legalDocumentsAPI.getAll();
      setDocuments(data || []);
    } catch (err: any) {
      console.error('Error loading documents:', err);
      setError(err?.message || 'Ошибка при загрузке документов');
    } finally {
      setLoading(false);
    }
  };

  const handleDocumentClick = (document: Document) => {
    if (document.url) {
      // Если есть URL, открываем во внешнем окне
      window.open(document.url, '_blank');
    } else if (document.content) {
      // Если есть контент, показываем в модалке
      setSelectedDocument(document);
      setIsDialogOpen(true);
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-background pb-20">
        <MiniAppHeader title="Документы" />
        <div className="flex flex-col items-center justify-center py-20">
          <Loader2 className="h-8 w-8 animate-spin text-primary" />
          <p className="mt-4 text-muted-foreground">Загрузка...</p>
        </div>
        <BottomNavigation />
      </div>
    );
  }

  if (error) {
    return (
      <div className="min-h-screen bg-background pb-20">
        <MiniAppHeader title="Документы" />
        <div className="flex flex-col items-center justify-center px-4 py-16">
          <p className="text-destructive">{error}</p>
          <button
            onClick={() => loadDocuments()}
            className="mt-4 h-11 rounded-lg bg-primary px-6 font-semibold text-primary-foreground touch-feedback"
          >
            Попробовать снова
          </button>
        </div>
        <BottomNavigation />
      </div>
    );
  }

  return (
    <>
      <div className="min-h-screen bg-background pb-20">
        <MiniAppHeader title="Документы" />

        <div className="px-4 py-4 space-y-2">
          {documents.length === 0 ? (
            <div className="py-12 text-center">
              <FileText className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
              <p className="text-muted-foreground">Документы пока не добавлены</p>
            </div>
          ) : (
            documents.map((document) => (
              <button
                key={document.id}
                onClick={() => handleDocumentClick(document)}
                className="w-full flex items-center justify-between p-4 rounded-lg border border-border bg-card hover:bg-accent transition-colors touch-feedback text-left"
              >
                <div className="flex items-center gap-3">
                  <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10">
                    <FileText className="h-5 w-5 text-primary" />
                  </div>
                  <div>
                    <h3 className="font-semibold text-foreground">
                      {DOCUMENT_TYPE_LABELS[document.type] || document.title}
                    </h3>
                    {document.url && (
                      <p className="text-xs text-muted-foreground mt-0.5 flex items-center gap-1">
                        <ExternalLink className="h-3 w-3" />
                        Внешняя ссылка
                      </p>
                    )}
                  </div>
                </div>
                <ChevronRight className="h-5 w-5 text-muted-foreground flex-shrink-0" />
              </button>
            ))
          )}
        </div>

        <BottomNavigation />
      </div>

      {/* Dialog для просмотра документа */}
      <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
        <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle>
              {selectedDocument
                ? DOCUMENT_TYPE_LABELS[selectedDocument.type] || selectedDocument.title
                : 'Документ'}
            </DialogTitle>
          </DialogHeader>
          {selectedDocument && (
            <div className="mt-4">
              {selectedDocument.content ? (
                <div
                  className="prose prose-sm max-w-none dark:prose-invert"
                  dangerouslySetInnerHTML={{ __html: selectedDocument.content.replace(/\n/g, '<br />') }}
                />
              ) : (
                <p className="text-muted-foreground">Содержимое документа отсутствует</p>
              )}
            </div>
          )}
        </DialogContent>
      </Dialog>
    </>
  );
}
