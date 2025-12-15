import { useEffect, useState } from 'react';
import toast from 'react-hot-toast';
import { Button } from '@/app/_components/ui/Button';
import { DashboardService } from '@/app/(admin)/putiroski/_services/dashboardService';

const EmailTemplatesTab = () => {
    const [templates, setTemplates] = useState([]);
    const [loading, setLoading] = useState(true);
    const [editingId, setEditingId] = useState(null);
    const [editForm, setEditForm] = useState({});
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        loadTemplates();
    }, []);

    const loadTemplates = async () => {
        try {
            setLoading(true);
            const data = await DashboardService.getEmailTemplates(); 
            setTemplates(data);
        } catch (error) {
            console.error(error);
            toast.error("Erro ao carregar templates. Verifique se você é Admin.");
        } finally {
            setLoading(false);
        }
    };

    const handleEdit = (template) => {
        setEditingId(template.id);
        setEditForm({ 
            subject: template.subject, 
            bodyContent: template.bodyContent 
        });
    };

    const handleCancel = () => {
        setEditingId(null);
        setEditForm({});
    };

    const handleSave = async (e) => {
        e.preventDefault();
        setSaving(true);
        try {
            await DashboardService.updateEmailTemplate(editingId, editForm);
            toast.success("Template atualizado com sucesso!");
            setEditingId(null);
            loadTemplates();
        } catch (error) {
            console.error(error);
            toast.error("Erro ao salvar template.");
        } finally {
            setSaving(false);
        }
    };

    if (loading) return <div className="p-4 text-center">Carregando templates...</div>;

    return (
        <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <h2 className="text-xl font-bold mb-6 flex items-center gap-2">
                ✉️ Templates de E-mail
                <span className="text-xs font-normal bg-blue-100 text-blue-800 px-2 py-1 rounded">System</span>
            </h2>
            
            <div className="space-y-6">
                {templates.length === 0 && <p className="text-gray-500">Nenhum template encontrado.</p>}
                
                {templates.map((tpl) => (
                    <div key={tpl.id} className={`border rounded-lg p-4 transition-colors ${editingId === tpl.id ? 'bg-white border-blue-500 ring-1 ring-blue-500' : 'bg-gray-50'}`}>
                        <div className="flex justify-between items-start mb-2">
                            <div>
                                <h3 className="font-bold text-blue-700 font-mono">{tpl.key}</h3>
                                <p className="text-xs text-gray-500">{tpl.description}</p>
                            </div>
                            {editingId !== tpl.id && (
                                <Button onClick={() => handleEdit(tpl)} variant="outline" size="sm" className="text-xs h-8">
                                    Editar
                                </Button>
                            )}
                        </div>

                        {editingId === tpl.id ? (
                            <form onSubmit={handleSave} className="space-y-4 mt-4">
                                <div>
                                    <label className="block text-sm font-bold mb-1 text-gray-700">Assunto do E-mail</label>
                                    <input 
                                        className="w-full border border-gray-300 p-2 rounded focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none"
                                        value={editForm.subject}
                                        onChange={e => setEditForm({...editForm, subject: e.target.value})}
                                        required
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-bold mb-1 text-gray-700">Corpo (HTML + Scriban)</label>
                                    <div className="bg-gray-800 text-gray-300 text-xs p-2 rounded-t flex justify-between">
                                        <span>Editor de Código</span>
                                        <span>Sintaxe: {'{{ variavel }}'}</span>
                                    </div>
                                    <textarea 
                                        className="w-full border border-gray-300 p-2 rounded-b font-mono text-sm h-80 focus:ring-2 focus:ring-blue-200 focus:border-blue-500 outline-none resize-y"
                                        value={editForm.bodyContent}
                                        onChange={e => setEditForm({...editForm, bodyContent: e.target.value})}
                                        spellCheck="false"
                                        required
                                    />
                                </div>
                                <div className="flex gap-3 justify-end pt-2">
                                    <Button type="button" onClick={handleCancel} variant="ghost" disabled={saving}>
                                        Cancelar
                                    </Button>
                                    <Button type="submit" disabled={saving}>
                                        {saving ? 'Salvando...' : 'Salvar Alterações'}
                                    </Button>
                                </div>
                            </form>
                        ) : (
                            <div className="text-sm text-gray-600 mt-2 border-t border-gray-200 pt-2">
                                <p><strong className="text-gray-800">Assunto Atual:</strong> {tpl.subject}</p>
                            </div>
                        )}
                    </div>
                ))}
            </div>
        </div>
    );
};

export default EmailTemplatesTab;