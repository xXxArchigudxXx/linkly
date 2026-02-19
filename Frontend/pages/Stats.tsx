import React, { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import api from '../services/api';
import { LinkStats } from '../types';
import { SERVER_BASE_URL } from '../constants';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';
import { ArrowLeft, Globe, Monitor, MousePointer2 } from 'lucide-react';
import toast from 'react-hot-toast';
import { useLanguageStore } from '../store/languageStore';

const Stats: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const [stats, setStats] = useState<LinkStats | null>(null);
  const [loading, setLoading] = useState(true);
  const { t } = useLanguageStore();

  useEffect(() => {
    const fetchStats = async () => {
      try {
        const response = await api.get<LinkStats>(`/user/links/${id}/stats`);
        setStats(response.data);
      } catch (error) {
        toast.error(t.failedLoadStats);
      } finally {
        setLoading(false);
      }
    };

    if (id) fetchStats();
  }, [id, t]);

  if (loading) {
    return (
      <div className="flex justify-center items-center min-h-[50vh]">
        <div className="w-8 h-8 border-4 border-primary-600 border-t-transparent rounded-full animate-spin"></div>
      </div>
    );
  }

  if (!stats) {
    return <div className="text-center mt-10 text-slate-500">{t.statsNotFound}</div>;
  }

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="mb-6">
        <Link to="/dashboard" className="text-slate-500 hover:text-primary-600 flex items-center mb-4 text-sm font-medium">
          <ArrowLeft className="h-4 w-4 mr-1" /> {t.backToDash}
        </Link>
        <h1 className="text-2xl font-bold text-slate-900">{t.analytics}</h1>
        <div className="mt-2 flex flex-col sm:flex-row sm:items-center gap-2 text-sm">
            <span className="text-primary-600 font-bold bg-primary-50 px-2 py-1 rounded">
                /{stats.short_code}
            </span>
            <span className="hidden sm:inline text-slate-300">|</span>
            <a href={stats.original_url} target="_blank" rel="noreferrer" className="text-slate-500 hover:underline truncate max-w-md block">
                {stats.original_url}
            </a>
        </div>
      </div>

      {/* Overview Cards */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm flex items-center">
            <div className="p-3 bg-blue-100 text-blue-600 rounded-lg mr-4">
                <MousePointer2 className="h-6 w-6" />
            </div>
            <div>
                <p className="text-sm font-medium text-slate-500">{t.totalClicks}</p>
                <p className="text-2xl font-bold text-slate-900">{stats.clicks}</p>
            </div>
        </div>
        <div className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm flex items-center">
            <div className="p-3 bg-green-100 text-green-600 rounded-lg mr-4">
                <Globe className="h-6 w-6" />
            </div>
            <div>
                <p className="text-sm font-medium text-slate-500">{t.topCountry}</p>
                <p className="text-2xl font-bold text-slate-900">
                    {stats.top_countries?.[0]?.country || "N/A"}
                </p>
            </div>
        </div>
        <div className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm flex items-center">
             <div className="p-3 bg-purple-100 text-purple-600 rounded-lg mr-4">
                <Monitor className="h-6 w-6" />
            </div>
            <div>
                <p className="text-sm font-medium text-slate-500">{t.topBrowser}</p>
                 <p className="text-2xl font-bold text-slate-900">
                    {stats.top_browsers?.[0]?.browser || "N/A"}
                </p>
            </div>
        </div>
      </div>

      {/* Main Chart */}
      <div className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm mb-8">
        <h2 className="text-lg font-bold text-slate-900 mb-6">{t.clicksOverTime}</h2>
        <div className="h-80 w-full">
            <ResponsiveContainer width="100%" height="100%">
            <BarChart data={stats.clicks_by_date}>
                <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#e2e8f0" />
                <XAxis 
                    dataKey="date" 
                    tick={{fill: '#64748b', fontSize: 12}} 
                    tickLine={false}
                    axisLine={false}
                    dy={10}
                />
                <YAxis 
                    tick={{fill: '#64748b', fontSize: 12}} 
                    tickLine={false}
                    axisLine={false}
                    allowDecimals={false}
                />
                <Tooltip 
                    cursor={{fill: '#f1f5f9'}}
                    contentStyle={{ borderRadius: '8px', border: 'none', boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)' }}
                />
                <Bar dataKey="count" fill="#4f46e5" radius={[4, 4, 0, 0]} barSize={40} />
            </BarChart>
            </ResponsiveContainer>
        </div>
      </div>

      {/* Breakdown Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
        {/* Countries */}
        <div className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
            <h2 className="text-lg font-bold text-slate-900 mb-4">{t.topCountries}</h2>
            <div className="space-y-4">
                {stats.top_countries.length > 0 ? stats.top_countries.map((item, index) => (
                    <div key={index} className="flex items-center justify-between">
                         <div className="flex items-center gap-2">
                             <div className="w-2 h-2 rounded-full bg-primary-500"></div>
                             <span className="text-slate-700 text-sm font-medium">{item.country}</span>
                         </div>
                         <span className="text-slate-900 font-bold text-sm">{item.count}</span>
                    </div>
                )) : (
                    <p className="text-slate-400 text-sm italic">{t.noData}</p>
                )}
            </div>
        </div>

        {/* Browsers */}
        <div className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
             <h2 className="text-lg font-bold text-slate-900 mb-4">{t.topBrowsers}</h2>
             <div className="space-y-4">
                {stats.top_browsers.length > 0 ? stats.top_browsers.map((item, index) => (
                    <div key={index} className="flex items-center justify-between">
                         <div className="flex items-center gap-2">
                             <div className="w-2 h-2 rounded-full bg-purple-500"></div>
                             <span className="text-slate-700 text-sm font-medium">{item.browser}</span>
                         </div>
                         <span className="text-slate-900 font-bold text-sm">{item.count}</span>
                    </div>
                )) : (
                    <p className="text-slate-400 text-sm italic">{t.noData}</p>
                )}
            </div>
        </div>
      </div>
    </div>
  );
};

export default Stats;
