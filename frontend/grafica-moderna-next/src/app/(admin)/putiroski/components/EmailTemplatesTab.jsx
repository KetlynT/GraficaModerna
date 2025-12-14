import { useEffect, useState } from 'react';
import toast from 'react-hot-toast';
import { Button } from '@/app/(website)/components/ui/Button';
import { ContentService } from '@/app/(website)/services/contentService';

const EmailTemplatesTab = () => {
    const [templates, setTemplates] = useState([]);
    const [loading, setLoading] = useState(true);
    const [editingId, setEditingId] = useState(null);
    const [editForm, setEditForm] = useState({});

    useEffect(() => {
        loadTemplates();
    }, []);

    const loadTemplates = async () => {
        try {
            const data = await ContentService.getEmailTemplates(); 
            setTemplates(data);
        } catch (error) {
            toast.error("Erro ao carregar templates");
        } finally {
            setLoading(false);
        }
    };

    const handleEdit = (template) => {
        setEditingId(template.id);
        setEditForm({ ...template });
    };

    const handleCancel = () => {
        setEditingId(null);
        setEditForm({});
    };

    const handleSave = async (e) => {
        e.preventDefault();
        try {
            await ContentService.updateEmailTemplate(editingId, editForm);
            toast.success("Template atualizado!");
            setEditingId(null);
            loadTemplates();
        } catch (error) {
            toast.error("Erro ao salvar template");
        }
    };

    return (
        <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <h2 className="text-xl font-bold mb-6">Templates de E-mail (Scriban)</h2>
            
            <div className="space-y-6">
                {loading ? <p>Carregando...</p> : templates.map((tpl) => (
                    <div key={tpl.id} className="border rounded-lg p-4 bg-gray-50">
                        <div className="flex justify-between items-start mb-2">
                            <div>
                                <h3 className="font-bold text-blue-700">{tpl.key}</h3>
                                <p className="text-xs text-gray-500">{tpl.description}</p>
                            </div>
                            {editingId !== tpl.id && (
                                <Button onClick={() => handleEdit(tpl)} variant="outline" className="text-xs">
                                    Editar
                                </Button>
                            )}
                        </div>

                        {editingId === tpl.id ? (
                            <form onSubmit={handleSave} className="space-y-3 mt-4 bg-white p-4 rounded border">
                                <div>
                                    <label className="block text-sm font-bold mb-1">Assunto</label>
                                    <input 
                                        className="w-full border p-2 rounded"
                                        value={editForm.subject}
                                        onChange={e => setEditForm({...editForm, subject: e.target.value})}
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-bold mb-1">HTML do Corpo</label>
                                    <p className="text-xs text-gray-400 mb-2">Variáveis disponíveis: {'{{ user_name }}'}, {'{{ order_number }}'}, etc.</p>
                                    <textarea 
                                        className="w-full border p-2 rounded font-mono text-sm h-64"
                                        value={editForm.bodyContent}
                                        onChange={e => setEditForm({...editForm, bodyContent: e.target.value})}
                                    />
                                </div>
                                <div className="flex gap-2 justify-end">
                                    <Button type="button" onClick={handleCancel} className="bg-gray-400 hover:bg-gray-500">Cancelar</Button>
                                    <Button type="submit">Salvar Alterações</Button>
                                </div>
                            </form>
                        ) : (
                            <div className="text-sm text-gray-600 mt-2 border-t pt-2">
                                <p><strong>Assunto:</strong> {tpl.subject}</p>
                            </div>
                        )}
                    </div>
                ))}
            </div>
        </div>
    );
};

export default EmailTemplatesTab;