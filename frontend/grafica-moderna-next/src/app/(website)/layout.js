import '../globals.css';
import { Header } from '@/app/(website)/components/layout/Header';
import { Footer } from '@/app/(website)/components/layout/Footer';
import { WhatsAppButton } from '@/app/(website)/components/WhatsAppButton';
import { Providers } from '@/app/(website)/components/Providers';
import { CookieConsent } from '@/app/(website)/components/CookieConsent';

export const metadata = {
  title: 'Gráfica Moderna',
  description: 'Sua gráfica online de confiança',
};

export default function RootLayout({ children }) {
  return (
    <html lang="pt-BR">
      <body className="bg-gray-50 min-h-screen flex flex-col">
          <Header />
          
          <main className="grow">
            <Providers>
              {children}
            </Providers>
          </main>

          <Footer />
          <WhatsAppButton />
          <CookieConsent />
      </body>
    </html>
  );
}