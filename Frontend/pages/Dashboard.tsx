import React, { useEffect, useState, useCallback } from 'react';
import api from '../services/api';
import { Link, PaginatedResponse } from '../types';
import { Link as LinkIcon, BarChart2, Trash2, Copy, Check, QrCode, Calendar, ExternalLink, Plus } from 'lucide-react';
import toast from 'react-hot-toast';
import { SERVER_BASE_URL } from '../constants';
import { useNavigate } from 'react-router-dom';
import { useLanguageStore } from '../store/languageStore';

const Dashboard: React.FC = () => {
  const [links, setLinks] = useState<Link[]>([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [copiedId, setCopiedId] = useState<number | null>(null);
  const [showCreateModal, setShowCreateModal] = useState(false);
  
  // Create Modal State
  const [newUrl, setNewUrl] = useState('');
  const [customCode, setCustomCode] = useState('');
  const [createLoading, setCreateLoading] = useState(false);

  const navigate = useNavigate();
  const { t } = useLanguageStore();

  const fetchLinks = useCallback(async (pageNum: number) => {
    setLoading(true);
    try {
      const response = await api.get<PaginatedResponse<Link>>(`/user/links`, {
        params: { page: pageNum, limit: 10 }
      });
      setLinks(response.data.data);
      setTotalPages(response.data.total_pages);
      setPage(response.data.page);
    } catch (error) {
      toast.error("Failed to load links.");
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchLinks(page);
  }, [fetchLinks, page]);

  const handleDelete = async (id: number) => {
    if (!window.confirm(t.deleteConfirm)) return;
    try {
      await api.delete(`/user/links/${id}`);
      setLinks(prev => prev.filter(link => link.id !== id));
      toast.success(t.linkDeleted);
    } catch (error) {
      toast.error(t.failedDelete);
    }
  };

  const copyToClipboard = (shortCode: string, id: number) => {
    const url = `${SERVER_BASE_URL}/${shortCode}`;
    navigator.clipboard.writeText(url);
    setCopiedId(id);
    toast.success(t.copied + "!");
    setTimeout(() => setCopiedId(null), 2000);
  };

  const handleCreate = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!newUrl) return;

    setCreateLoading(true);
    try {
      await api.post('/links', { 
        original_url: newUrl, 
        custom_code: customCode || undefined 
      });
      toast.success(t.linkCreated);
      setShowCreateModal(false);
      setNewUrl('');
      setCustomCode('');
      fetchLinks(1); // Refresh list
    } catch (error: any) {
      toast.error(error.response?.data?.message || t.failedCreate);
    } finally {
      setCreateLoading(false);
    }
  };

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <h1 className="text-2xl font-bold text-slate-900">{t.dashTitle}</h1>
            <p className="text-slate-500 text-sm mt-1">{t.dashSubtitle}</p>
        </div>
        <button
          onClick={() => setShowCreateModal(true)}
          className="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
        >
          <Plus className="h-4 w-4 mr-2" />
          {t.createLink}
        </button>
      </div>

      {loading && links.length === 0 ? (
        <div className="space-y-4">
          {[1, 2, 3].map(i => (
            <div key={i} className="bg-white p-6 rounded-xl border border-slate-200 animate-pulse h-24"></div>
          ))}
        </div>
      ) : links.length === 0 ? (
         <div className="text-center py-20 bg-white rounded-xl border border-slate-200 border-dashed">
            <div className="bg-slate-50 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                <LinkIcon className="h-8 w-8 text-slate-400" />
            </div>
            <h3 className="text-lg font-medium text-slate-900">{t.noLinks}</h3>
            <p className="text-slate-500 mt-1 mb-6">{t.noLinksSub}</p>
            <button
                onClick={() => setShowCreateModal(true)}
                className="text-primary-600 font-medium hover:text-primary-700"
            >
                {t.createLinkArrow} &rarr;
            </button>
         </div>
      ) : (
        <div className="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
          <ul className="divide-y divide-slate-200">
            {links.map((link) => (
              <li key={link.id} className="p-4 sm:p-6 hover:bg-slate-50 transition-colors">
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2 mb-1">
                      <a 
                        href={`${SERVER_BASE_URL}/${link.short_code}`}
                        target="_blank"
                        rel="noreferrer"
                        className="text-lg font-bold text-primary-600 hover:text-primary-700 truncate"
                      >
                        {SERVER_BASE_URL.replace(/^https?:\/\//, '')}/{link.short_code}
                      </a>
                       <button
                        onClick={() => copyToClipboard(link.short_code, link.id)}
                        className="text-slate-400 hover:text-primary-600 transition-colors"
                        title="Copy"
                      >
                        {copiedId === link.id ? <Check className="h-4 w-4 text-green-500" /> : <Copy className="h-4 w-4" />}
                      </button>
                    </div>
                    <div className="flex items-center text-sm text-slate-500 truncate max-w-lg mb-2">
                        <ExternalLink className="h-3 w-3 mr-1 flex-shrink-0" />
                        <span className="truncate">{link.original_url}</span>
                    </div>
                    <div className="flex items-center gap-4 text-xs text-slate-400">
                        <span className="flex items-center">
                            <Calendar className="h-3 w-3 mr-1" />
                            {new Date(link.created_at).toLocaleDateString()}
                        </span>
                        <span className="flex items-center bg-slate-100 px-2 py-0.5 rounded-full text-slate-600 font-medium">
                            <BarChart2 className="h-3 w-3 mr-1" />
                            {link.clicks} {t.clicks}
                        </span>
                    </div>
                  </div>

                  <div className="flex items-center gap-2 self-start sm:self-center">
                    <div className="relative group">
                        <img 
                            src={`${SERVER_BASE_URL}/${link.short_code}/qr`} 
                            alt="QR" 
                            className="w-10 h-10 border border-slate-200 rounded p-1 hidden sm:block"
                        />
                         <div className="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-32 bg-white p-2 rounded shadow-xl border border-slate-100 hidden group-hover:block z-10">
                            <img 
                                src={`${SERVER_BASE_URL}/${link.short_code}/qr`} 
                                alt="QR Large" 
                                className="w-full h-auto"
                            />
                         </div>
                    </div>
                    
                    <button
                      onClick={() => navigate(`/stats/${link.id}`)}
                      className="inline-flex items-center px-3 py-2 border border-slate-300 shadow-sm text-sm leading-4 font-medium rounded-md text-slate-700 bg-white hover:bg-slate-50 focus:outline-none"
                    >
                      <BarChart2 className="h-4 w-4 mr-1" /> {t.stats}
                    </button>
                    <button
                      onClick={() => handleDelete(link.id)}
                      className="inline-flex items-center p-2 border border-transparent rounded-md text-red-600 hover:bg-red-50 focus:outline-none"
                      title="Delete"
                    >
                      <Trash2 className="h-4 w-4" />
                    </button>
                  </div>
                </div>
              </li>
            ))}
          </ul>
        </div>
      )}

      {/* Pagination */}
      {totalPages > 1 && (
        <div className="flex justify-center mt-8 gap-2">
          <button
            onClick={() => setPage(p => Math.max(1, p - 1))}
            disabled={page === 1}
            className="px-4 py-2 border border-slate-300 rounded-md text-sm font-medium text-slate-700 bg-white hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {t.prev}
          </button>
          <span className="px-4 py-2 text-sm text-slate-600 self-center">
            {t.page} {page} {t.of} {totalPages}
          </span>
          <button
            onClick={() => setPage(p => Math.min(totalPages, p + 1))}
            disabled={page === totalPages}
            className="px-4 py-2 border border-slate-300 rounded-md text-sm font-medium text-slate-700 bg-white hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {t.next}
          </button>
        </div>
      )}

      {/* Create Modal */}
      {showCreateModal && (
        <div className="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
          <div className="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div className="fixed inset-0 bg-slate-500 bg-opacity-75 transition-opacity" onClick={() => setShowCreateModal(false)}></div>
            <span className="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div className="relative inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
              <div className="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div className="sm:flex sm:items-start">
                  <div className="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                    <h3 className="text-lg leading-6 font-medium text-slate-900" id="modal-title">{t.createModalTitle}</h3>
                    <div className="mt-4 space-y-4">
                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-1">{t.destUrl}</label>
                            <input 
                                type="url" 
                                required
                                className="w-full border-slate-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500 border p-2"
                                placeholder="https://example.com/very-long-url"
                                value={newUrl}
                                onChange={e => setNewUrl(e.target.value)}
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-1">{t.customCode}</label>
                            <div className="flex rounded-md shadow-sm">
                                <span className="inline-flex items-center px-3 rounded-l-md border border-r-0 border-slate-300 bg-slate-50 text-slate-500 text-sm">
                                    {SERVER_BASE_URL.replace(/^https?:\/\//, '')}/
                                </span>
                                <input
                                    type="text"
                                    className="flex-1 min-w-0 block w-full px-3 py-2 rounded-none rounded-r-md focus:ring-primary-500 focus:border-primary-500 border-slate-300 border sm:text-sm"
                                    placeholder="my-link"
                                    value={customCode}
                                    onChange={e => setCustomCode(e.target.value)}
                                />
                            </div>
                        </div>
                    </div>
                  </div>
                </div>
              </div>
              <div className="bg-slate-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button
                  type="button"
                  onClick={handleCreate}
                  disabled={createLoading}
                  className="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-70"
                >
                  {createLoading ? t.creating : t.create}
                </button>
                <button
                  type="button"
                  onClick={() => setShowCreateModal(false)}
                  className="mt-3 w-full inline-flex justify-center rounded-md border border-slate-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                >
                  {t.cancel}
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default Dashboard;
