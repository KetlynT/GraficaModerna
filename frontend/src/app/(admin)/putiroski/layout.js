import '@/app/globals.css';

export const metadata = {
  title: 'Admin - Gráfica Moderna',
  description: 'Painel Administrativo',
  robots: 'noindex, nofollow',
};

export default function AdminRootLayout({ children }) {
  return (
    <html lang="pt-BR">
      <body className="bg-gray-100 min-h-screen">
        {children}
      </body>
    </html>
  );
}