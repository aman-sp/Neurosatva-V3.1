import './globals.css';

export const metadata = {
  title: 'Neurosatva Portal',
  description: 'Vercel frontend for the Neurosatva PHP backend split architecture.'
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="en">
      <body>{children}</body>
    </html>
  );
}
