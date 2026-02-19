import React, { useState } from 'react';
import { ArrowRight, Copy, Check, QrCode, Link as LinkIcon } from 'lucide-react';
import toast from 'react-hot-toast';
import api from '../services/api';
import { SERVER_BASE_URL } from '../constants';
import { useAuthStore } from '../store/authStore';
import { useLanguageStore } from '../store/languageStore';
import { Link } from 'react-router-dom';

const Home: React.FC = () => {
  const [originalUrl, setOriginalUrl] = useState('');
  const [shortUrl, setShortUrl] = useState<string | null>(null);
  const [shortCode, setShortCode] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const [copied, setCopied] = useState(false);
  const { isAuthenticated } = useAuthStore();
  const { t } = useLanguageStore();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!originalUrl) return;

    // Basic validation
    try {
      new URL(originalUrl);
    } catch {
      toast.error(t.errorInvalidUrl);
      return;
    }

    setLoading(true);
    setShortUrl(null);
    setShortCode(null);
    setCopied(false);

    try {
      // POST /api/v1/links
      const response = await api.post('/links', { original_url: originalUrl });
      const { short_code } = response.data;
      
      const fullShortUrl = `${SERVER_BASE_URL}/${short_code}`;
      setShortUrl(fullShortUrl);
      setShortCode(short_code);
      toast.success(t.successShortened);
    } catch (error: any) {
      toast.error(error.response?.data?.message || t.errorShorten);
    } finally {
      setLoading(false);
    }
  };

  const copyToClipboard = () => {
    if (shortUrl) {
      navigator.clipboard.writeText(shortUrl);
      setCopied(true);
      toast.success(t.copied + "!");
      setTimeout(() => setCopied(false), 2000);
    }
  };

  return (
    <div className="flex flex-col items-center justify-center pt-16 pb-24 px-4 sm:px-6 lg:px-8">
      {/* Hero Section */}
      <div className="text-center max-w-3xl mx-auto mb-12">
        <h1 className="text-4xl sm:text-5xl font-extrabold text-slate-900 tracking-tight mb-6">
          {t.heroTitlePrefix} <span className="text-primary-600">{t.heroTitleSuffix}</span>
        </h1>
        <p className="text-lg text-slate-600 mb-8 max-w-2xl mx-auto">
          {t.heroSubtitle}
        </p>
      </div>

      {/* Shortener Card */}
      <div className="w-full max-w-2xl bg-white rounded-2xl shadow-xl border border-slate-100 p-6 sm:p-8">
        <form onSubmit={handleSubmit} className="relative">
          <div className="flex flex-col sm:flex-row gap-4">
            <div className="relative flex-grow">
              <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <LinkIcon className="h-5 w-5 text-slate-400" />
              </div>
              <input
                type="text"
                className="block w-full pl-10 pr-4 py-4 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                placeholder={t.placeholder}
                value={originalUrl}
                onChange={(e) => setOriginalUrl(e.target.value)}
              />
            </div>
            <button
              type="submit"
              disabled={loading}
              className="inline-flex justify-center items-center px-8 py-4 border border-transparent text-base font-medium rounded-xl text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all disabled:opacity-70 disabled:cursor-not-allowed shadow-lg shadow-primary-500/30"
            >
              {loading ? (
                <span className="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
              ) : (
                <>
                  {t.shortenButton} <ArrowRight className="ml-2 h-5 w-5" />
                </>
              )}
            </button>
          </div>
        </form>

        {/* Result Area */}
        {shortUrl && shortCode && (
          <div className="mt-8 p-6 bg-slate-50 rounded-xl border border-slate-200 animate-fade-in-up">
            <div className="flex flex-col sm:flex-row items-center justify-between gap-4">
              <div className="w-full sm:w-auto overflow-hidden">
                <p className="text-sm text-slate-500 mb-1">{t.yourShortLink}</p>
                <a 
                  href={shortUrl} 
                  target="_blank" 
                  rel="noopener noreferrer"
                  className="text-lg sm:text-xl font-bold text-primary-600 hover:text-primary-700 break-all"
                >
                  {shortUrl}
                </a>
              </div>
              
              <div className="flex gap-2 w-full sm:w-auto">
                <button
                  onClick={copyToClipboard}
                  className={`flex-1 sm:flex-none inline-flex items-center justify-center px-4 py-2 border rounded-lg text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-primary-500 ${
                    copied
                      ? 'bg-green-50 text-green-700 border-green-200'
                      : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50'
                  }`}
                >
                  {copied ? (
                    <>
                      <Check className="h-4 w-4 mr-2" /> {t.copied}
                    </>
                  ) : (
                    <>
                      <Copy className="h-4 w-4 mr-2" /> {t.copy}
                    </>
                  )}
                </button>
              </div>
            </div>

            <div className="mt-6 flex flex-col items-center sm:items-start border-t border-slate-200 pt-6">
               <div className="flex items-center gap-2 mb-4">
                 <QrCode className="w-4 h-4 text-slate-500"/>
                 <span className="text-sm font-medium text-slate-700">{t.qrCode}</span>
               </div>
               <img 
                src={`${SERVER_BASE_URL}/${shortCode}/qr`} 
                alt="QR Code" 
                className="w-32 h-32 border border-slate-200 rounded-lg p-2 bg-white"
               />
            </div>
          </div>
        )}
      </div>
      
      {!isAuthenticated && (
        <div className="mt-8 text-center">
            <p className="text-slate-500">
                {t.wantToTrack}{' '}
                <Link to="/register" className="text-primary-600 font-medium hover:underline">
                    {t.createAccountLink}
                </Link>
            </p>
        </div>
      )}
    </div>
  );
};

export default Home;
