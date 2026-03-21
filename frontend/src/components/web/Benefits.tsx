import { Truck, Award, Percent } from 'lucide-react';

const benefits = [
  {
    id: 'delivery',
    icon: Truck,
    title: 'Быстрая доставка',
    description: 'Доставим за 1–2 часа. Бесплатная доставка при заказе от 3000 ₽',
  },
  {
    id: 'quality',
    icon: Award,
    title: 'Качество',
    description: 'Свежая выпечка из отборной муки. Печём каждый день',
  },
  {
    id: 'promo',
    icon: Percent,
    title: 'Акции и скидки',
    description: 'Специальные предложения для постоянных клиентов',
  },
];

export function Benefits() {
  return (
    <section id="benefits" className="border-t border-border bg-muted/20 py-16">
      <div className="container mx-auto px-4 lg:px-8">
        <h2 className="mb-12 text-center text-2xl font-bold tracking-tight md:text-3xl">
          Почему выбирают нас
        </h2>
        <div className="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
          {benefits.map((item) => (
            <div
              key={item.id}
              className="flex flex-col items-center rounded-xl border border-border bg-card p-8 text-center shadow-sm transition-colors hover:border-primary/30 hover:bg-card/80"
            >
              <div className="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-primary/10 text-primary">
                <item.icon className="h-7 w-7" />
              </div>
              <h3 className="mb-2 text-lg font-semibold">{item.title}</h3>
              <p className="text-sm text-muted-foreground">{item.description}</p>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
