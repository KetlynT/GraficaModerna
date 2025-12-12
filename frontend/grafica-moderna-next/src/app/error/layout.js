'use client'

import './globals.css';
import { Providers } from '@/app/(website)/components/Providers';

export default function RootLayout({ children }) {
  return (
    <html lang="pt-BR">
      <body className="bg-gray-50 min-h-screen flex flex-col">
        <Providers>
          <main className="grow">
            {children}
          </main>
        </Providers>
      </body>
    </html>
  );
}