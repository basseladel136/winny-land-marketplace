import { useEffect, useRef, useState } from "react";
import { createFileRoute, Link, useNavigate } from "@tanstack/react-router";
import { motion } from "framer-motion";
import {
  Calendar,
  Camera,
  CreditCard,
  Heart,
  Loader2,
  Lock,
  LogOut,
  Mail,
  MapPin,
  Package,
  Phone,
  Receipt,
  Star,
  Trash2,
  Wallet,
} from "lucide-react";
import { toast } from "sonner";

import { MarketplaceNav } from "@/components/MarketplaceNav";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Skeleton } from "@/components/ui/skeleton";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Textarea } from "@/components/ui/textarea";
import { useAuthStore } from "@/lib/authStore";
import { useStore } from "@/lib/store";
import { formatPrice } from "@/lib/utils";
import { authApi, ordersApi, type ApiOrder, type ApiUser, type ProfileStats } from "@/lib/api";

export const Route = createFileRoute("/marketplace/profile")({
  component: ProfilePage,
});

function ProfilePage() {
  const navigate = useNavigate();
  const { isAuthenticated, user: storedUser, setUser, logout } = useAuthStore();

  const [user, setLocalUser] = useState<ApiUser | null>(storedUser);
  const [stats, setStats] = useState<ProfileStats | null>(null);
  const [orders, setOrders] = useState<ApiOrder[]>([]);
  const [ordersLoading, setOrdersLoading] = useState(true);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (!isAuthenticated) {
      navigate({ to: "/login" });
      return;
    }
    loadProfile();
    loadOrders();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [isAuthenticated]);

  async function loadProfile() {
    setLoading(true);
    try {
      const [meRes, statsRes] = await Promise.all([authApi.me(), authApi.stats()]);
      setLocalUser(meRes.user);
      setUser(meRes.user);
      setStats(statsRes.data);
    } catch (err) {
      toast.error(err instanceof Error ? err.message : "Failed to load profile");
    } finally {
      setLoading(false);
    }
  }

  async function loadOrders() {
    setOrdersLoading(true);
    try {
      const res = await ordersApi.list();
      setOrders(res.data);
    } catch {
      // Orders are non-critical for the page to render; ignore failures.
    } finally {
      setOrdersLoading(false);
    }
  }

  function handleUserUpdated(updated: ApiUser) {
    setLocalUser(updated);
    setUser(updated);
  }

  async function handleLogout() {
    await logout();
    toast.success("Logged out");
    navigate({ to: "/marketplace" });
  }

  if (loading || !user) return <ProfileSkeleton />;

  const memberSince = user.createdAt
    ? new Date(user.createdAt).toLocaleDateString(undefined, {
        year: "numeric",
        month: "long",
        day: "numeric",
      })
    : "—";

  return (
    <div className="min-h-screen bg-background">
      <MarketplaceNav />
      <div className="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.4 }}
          className="flex flex-wrap items-start justify-between gap-4"
        >
          <div>
            <h1 className="text-2xl font-bold tracking-tight">My Profile</h1>
            <p className="mt-1 text-sm text-muted-foreground">
              Manage your account details and preferences.
            </p>
          </div>
          <Button variant="outline" onClick={handleLogout} className="gap-2">
            <LogOut className="h-4 w-4" />
            Log out
          </Button>
        </motion.div>

        <div className="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-3">
          {/* Summary card */}
          <div className="space-y-6 lg:col-span-1">
            <ProfileSummary user={user} memberSince={memberSince} onAvatarUpdated={handleUserUpdated} />
            <ProfileStatsGrid stats={stats} />
          </div>

          {/* Forms */}
          <div className="lg:col-span-2">
            <Tabs defaultValue="details">
              <TabsList className="grid w-full grid-cols-3 sm:grid-cols-6">
                <TabsTrigger value="details">Details</TabsTrigger>
                <TabsTrigger value="orders">Orders</TabsTrigger>
                <TabsTrigger value="wishlist">Wishlist</TabsTrigger>
                <TabsTrigger value="payments">Payments</TabsTrigger>
                <TabsTrigger value="addresses">Addresses</TabsTrigger>
                <TabsTrigger value="security">Security</TabsTrigger>
              </TabsList>
              <TabsContent value="details" className="mt-4">
                <ProfileDetailsForm user={user} onUpdated={handleUserUpdated} />
              </TabsContent>
              <TabsContent value="orders" className="mt-4">
                <OrdersTab orders={orders} loading={ordersLoading} />
              </TabsContent>
              <TabsContent value="wishlist" className="mt-4">
                <WishlistTab />
              </TabsContent>
              <TabsContent value="payments" className="mt-4">
                <PaymentsTab orders={orders} loading={ordersLoading} />
              </TabsContent>
              <TabsContent value="addresses" className="mt-4">
                <AddressesTab user={user} onUpdated={handleUserUpdated} />
              </TabsContent>
              <TabsContent value="security" className="mt-4">
                <PasswordForm />
              </TabsContent>
            </Tabs>
          </div>
        </div>
      </div>
    </div>
  );
}

// ---------------------------------------------------------------------------
// Summary card with avatar upload
// ---------------------------------------------------------------------------
function ProfileSummary({
  user,
  memberSince,
  onAvatarUpdated,
}: {
  user: ApiUser;
  memberSince: string;
  onAvatarUpdated: (u: ApiUser) => void;
}) {
  const fileInput = useRef<HTMLInputElement>(null);
  const [uploading, setUploading] = useState(false);

  const initials = user.name
    .split(" ")
    .map((n) => n.charAt(0))
    .slice(0, 2)
    .join("")
    .toUpperCase();

  const handleFile = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;
    if (file.size > 2 * 1024 * 1024) {
      toast.error("Image must be 2MB or smaller");
      return;
    }
    setUploading(true);
    try {
      const res = await authApi.updateAvatar(file);
      onAvatarUpdated(res.user);
      toast.success("Avatar updated");
    } catch (err) {
      toast.error(err instanceof Error ? err.message : "Failed to upload avatar");
    } finally {
      setUploading(false);
      if (fileInput.current) fileInput.current.value = "";
    }
  };

  return (
    <Card>
      <CardContent className="flex flex-col items-center p-6 text-center">
        <div className="relative">
          <Avatar className="h-24 w-24">
            {user.avatar && <AvatarImage src={user.avatar} alt={user.name} />}
            <AvatarFallback className="bg-primary/10 text-xl font-semibold text-primary">
              {initials || "U"}
            </AvatarFallback>
          </Avatar>
          <button
            type="button"
            onClick={() => fileInput.current?.click()}
            disabled={uploading}
            className="absolute -bottom-1 -right-1 flex h-8 w-8 items-center justify-center rounded-full bg-primary text-primary-foreground shadow transition-colors hover:bg-primary/90 disabled:opacity-60"
            aria-label="Change avatar"
          >
            {uploading ? <Loader2 className="h-4 w-4 animate-spin" /> : <Camera className="h-4 w-4" />}
          </button>
          <input
            ref={fileInput}
            type="file"
            accept="image/png,image/jpeg,image/webp"
            className="hidden"
            onChange={handleFile}
          />
        </div>

        <h2 className="mt-4 text-lg font-semibold">{user.name}</h2>
        <p className="text-sm text-muted-foreground">{user.email}</p>

        <div className="mt-6 w-full space-y-3 text-left text-sm">
          <InfoRow icon={<Mail className="h-4 w-4" />} value={user.email} />
          <InfoRow icon={<Phone className="h-4 w-4" />} value={user.phone || "No phone added"} muted={!user.phone} />
          <InfoRow icon={<MapPin className="h-4 w-4" />} value={user.address || "No address added"} muted={!user.address} />
          <InfoRow icon={<Calendar className="h-4 w-4" />} value={`Member since ${memberSince}`} />
        </div>
      </CardContent>
    </Card>
  );
}

function InfoRow({ icon, value, muted }: { icon: React.ReactNode; value: string; muted?: boolean }) {
  return (
    <div className="flex items-center gap-3">
      <span className="text-muted-foreground">{icon}</span>
      <span className={muted ? "text-muted-foreground" : "text-foreground"}>{value}</span>
    </div>
  );
}

// ---------------------------------------------------------------------------
// Stats
// ---------------------------------------------------------------------------
function ProfileStatsGrid({ stats }: { stats: ProfileStats | null }) {
  const items = [
    { label: "Orders", value: stats?.ordersCount ?? 0, icon: <Package className="h-4 w-4" /> },
    {
      label: "Total Spent",
      value: stats ? formatPrice(stats.totalSpent) : formatPrice(0),
      icon: <Wallet className="h-4 w-4" />,
    },
    { label: "Wishlist", value: stats?.wishlistCount ?? 0, icon: <Heart className="h-4 w-4" /> },
    { label: "Reviews", value: stats?.reviewsCount ?? 0, icon: <Star className="h-4 w-4" /> },
  ];

  return (
    <div className="grid grid-cols-2 gap-3">
      {items.map((item) => (
        <Card key={item.label}>
          <CardContent className="p-4">
            <div className="flex items-center gap-2 text-muted-foreground">
              {item.icon}
              <span className="text-xs font-medium">{item.label}</span>
            </div>
            <p className="mt-2 text-xl font-bold">{item.value}</p>
          </CardContent>
        </Card>
      ))}
    </div>
  );
}

// ---------------------------------------------------------------------------
// Edit profile form
// ---------------------------------------------------------------------------
function ProfileDetailsForm({ user, onUpdated }: { user: ApiUser; onUpdated: (u: ApiUser) => void }) {
  const [name, setName] = useState(user.name);
  const [phone, setPhone] = useState(user.phone ?? "");
  const [saving, setSaving] = useState(false);

  const dirty = name !== user.name || phone !== (user.phone ?? "");

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!name.trim()) {
      toast.error("Name is required");
      return;
    }
    setSaving(true);
    try {
      const res = await authApi.updateProfile({
        name: name.trim(),
        phone: phone.trim() || null,
      });
      onUpdated(res.user);
      toast.success("Profile updated");
    } catch (err) {
      toast.error(err instanceof Error ? err.message : "Failed to update profile");
    } finally {
      setSaving(false);
    }
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle>Profile Details</CardTitle>
      </CardHeader>
      <CardContent>
        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="name">Full Name</Label>
            <Input id="name" value={name} onChange={(e) => setName(e.target.value)} required />
          </div>
          <div className="space-y-2">
            <Label htmlFor="email">Email</Label>
            <Input id="email" type="email" value={user.email} disabled />
            <p className="text-xs text-muted-foreground">Email can't be changed.</p>
          </div>
          <div className="space-y-2">
            <Label htmlFor="phone">Phone</Label>
            <Input
              id="phone"
              value={phone}
              onChange={(e) => setPhone(e.target.value)}
              placeholder="+20 100 000 0000"
            />
          </div>
          <Button type="submit" disabled={saving || !dirty}>
            {saving && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
            Save Changes
          </Button>
        </form>
      </CardContent>
    </Card>
  );
}

// ---------------------------------------------------------------------------
// Orders tab — order history
// ---------------------------------------------------------------------------
const STATUS_STYLES: Record<string, string> = {
  pending: "bg-amber-500/10 text-amber-600 dark:text-amber-400",
  processing: "bg-blue-500/10 text-blue-600 dark:text-blue-400",
  shipped: "bg-indigo-500/10 text-indigo-600 dark:text-indigo-400",
  delivered: "bg-green-500/10 text-green-600 dark:text-green-400",
  cancelled: "bg-red-500/10 text-red-600 dark:text-red-400",
};

const PAYMENT_STATUS_STYLES: Record<string, string> = {
  pending: "bg-amber-500/10 text-amber-600 dark:text-amber-400",
  paid: "bg-green-500/10 text-green-600 dark:text-green-400",
  failed: "bg-red-500/10 text-red-600 dark:text-red-400",
  refunded: "bg-muted text-muted-foreground",
};

function formatDate(iso: string): string {
  return new Date(iso).toLocaleDateString(undefined, {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
}

function OrdersTab({ orders, loading }: { orders: ApiOrder[]; loading: boolean }) {
  if (loading) return <ListSkeleton />;

  if (orders.length === 0) {
    return (
      <EmptyState
        icon={<Package className="h-8 w-8" />}
        title="No orders yet"
        description="When you place an order it will show up here."
      />
    );
  }

  return (
    <div className="space-y-3">
      {orders.map((order) => (
        <Card key={order.id}>
          <CardContent className="p-4">
            <div className="flex flex-wrap items-start justify-between gap-2">
              <div>
                <p className="font-medium">{order.orderNumber}</p>
                <p className="text-xs text-muted-foreground">{formatDate(order.createdAt)}</p>
              </div>
              <Badge variant="secondary" className={STATUS_STYLES[order.status] ?? ""}>
                {order.status}
              </Badge>
            </div>
            <div className="mt-3 flex items-center justify-between text-sm">
              <span className="text-muted-foreground">
                {order.items?.length ?? 0} item{(order.items?.length ?? 0) === 1 ? "" : "s"}
              </span>
              <span className="font-semibold">{formatPrice(order.total)}</span>
            </div>
          </CardContent>
        </Card>
      ))}
    </div>
  );
}

// ---------------------------------------------------------------------------
// Wishlist tab — products hearted on the marketplace (local store)
// ---------------------------------------------------------------------------
function WishlistTab() {
  const wishlist = useStore((s) => s.wishlist);
  const products = useStore((s) => s.products);
  const toggleWishlist = useStore((s) => s.toggleWishlist);

  const items = products.filter((p) => wishlist.includes(p.id));

  if (items.length === 0) {
    return (
      <EmptyState
        icon={<Heart className="h-8 w-8" />}
        title="Your wishlist is empty"
        description="Tap the heart on any product to save it here."
      />
    );
  }

  return (
    <div className="space-y-3">
      {items.map((product) => (
        <Card key={product.id}>
          <CardContent className="flex items-center gap-4 p-4">
            <Link
              to="/marketplace/$slug"
              params={{ slug: product.id }}
              className="h-16 w-16 shrink-0 overflow-hidden rounded-lg bg-muted"
            >
              <img src={product.image} alt={product.name} className="h-full w-full object-cover" />
            </Link>
            <div className="min-w-0 flex-1">
              <Link
                to="/marketplace/$slug"
                params={{ slug: product.id }}
                className="block truncate font-medium hover:text-pink"
              >
                {product.name}
              </Link>
              <p className="text-sm text-muted-foreground">{formatPrice(product.price)}</p>
            </div>
            <Button
              variant="ghost"
              size="icon"
              onClick={() => toggleWishlist(product.id)}
              aria-label="Remove from wishlist"
              className="text-muted-foreground hover:text-destructive"
            >
              <Trash2 className="h-4 w-4" />
            </Button>
          </CardContent>
        </Card>
      ))}
    </div>
  );
}

// ---------------------------------------------------------------------------
// Payments tab — payment history derived from orders
// ---------------------------------------------------------------------------
function PaymentsTab({ orders, loading }: { orders: ApiOrder[]; loading: boolean }) {
  if (loading) return <ListSkeleton />;

  if (orders.length === 0) {
    return (
      <EmptyState
        icon={<CreditCard className="h-8 w-8" />}
        title="No payments yet"
        description="Your payment history will appear here after your first order."
      />
    );
  }

  return (
    <div className="space-y-3">
      {orders.map((order) => (
        <Card key={order.id}>
          <CardContent className="flex items-center gap-4 p-4">
            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
              <Receipt className="h-5 w-5" />
            </div>
            <div className="min-w-0 flex-1">
              <p className="truncate font-medium">{order.orderNumber}</p>
              <p className="text-xs text-muted-foreground">
                {order.paymentMethod === "cod" ? "Cash on Delivery" : "Card (Paymob)"}
                {order.paymentReference ? ` · ${order.paymentReference}` : ""}
              </p>
            </div>
            <div className="text-right">
              <p className="font-semibold">{formatPrice(order.total)}</p>
              <Badge
                variant="secondary"
                className={PAYMENT_STATUS_STYLES[order.paymentStatus] ?? ""}
              >
                {order.paymentStatus}
              </Badge>
            </div>
          </CardContent>
        </Card>
      ))}
    </div>
  );
}

// ---------------------------------------------------------------------------
// Addresses tab — manage the shipping address
// ---------------------------------------------------------------------------
function AddressesTab({ user, onUpdated }: { user: ApiUser; onUpdated: (u: ApiUser) => void }) {
  const [address, setAddress] = useState(user.address ?? "");
  const [saving, setSaving] = useState(false);

  const dirty = address !== (user.address ?? "");

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    try {
      const res = await authApi.updateProfile({ address: address.trim() || null });
      onUpdated(res.user);
      toast.success("Address saved");
    } catch (err) {
      toast.error(err instanceof Error ? err.message : "Failed to save address");
    } finally {
      setSaving(false);
    }
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <MapPin className="h-4 w-4" />
          Shipping Address
        </CardTitle>
      </CardHeader>
      <CardContent>
        <form onSubmit={handleSubmit} className="space-y-4">
          <p className="text-sm text-muted-foreground">
            This address is used as the default for checkout.
          </p>
          <div className="space-y-2">
            <Label htmlFor="address">Address</Label>
            <Textarea
              id="address"
              value={address}
              onChange={(e) => setAddress(e.target.value)}
              placeholder="Street, building, city, governorate"
              rows={4}
              maxLength={500}
            />
          </div>
          <Button type="submit" disabled={saving || !dirty}>
            {saving && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
            Save Address
          </Button>
        </form>
      </CardContent>
    </Card>
  );
}

// ---------------------------------------------------------------------------
// Shared bits
// ---------------------------------------------------------------------------
function EmptyState({
  icon,
  title,
  description,
}: {
  icon: React.ReactNode;
  title: string;
  description: string;
}) {
  return (
    <Card>
      <CardContent className="flex flex-col items-center justify-center gap-2 py-12 text-center">
        <div className="flex h-14 w-14 items-center justify-center rounded-full bg-muted text-muted-foreground">
          {icon}
        </div>
        <p className="font-medium">{title}</p>
        <p className="max-w-xs text-sm text-muted-foreground">{description}</p>
      </CardContent>
    </Card>
  );
}

function ListSkeleton() {
  return (
    <div className="space-y-3">
      {Array.from({ length: 3 }).map((_, i) => (
        <Skeleton key={i} className="h-20 w-full rounded-xl" />
      ))}
    </div>
  );
}

// ---------------------------------------------------------------------------
// Change password form
// ---------------------------------------------------------------------------
function PasswordForm() {
  const [current, setCurrent] = useState("");
  const [next, setNext] = useState("");
  const [confirm, setConfirm] = useState("");
  const [saving, setSaving] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (next.length < 8) {
      toast.error("New password must be at least 8 characters");
      return;
    }
    if (next !== confirm) {
      toast.error("Passwords do not match");
      return;
    }
    setSaving(true);
    try {
      await authApi.updatePassword({
        current_password: current,
        password: next,
        password_confirmation: confirm,
      });
      toast.success("Password updated");
      setCurrent("");
      setNext("");
      setConfirm("");
    } catch (err) {
      toast.error(err instanceof Error ? err.message : "Failed to update password");
    } finally {
      setSaving(false);
    }
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <Lock className="h-4 w-4" />
          Change Password
        </CardTitle>
      </CardHeader>
      <CardContent>
        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="current_password">Current Password</Label>
            <Input
              id="current_password"
              type="password"
              value={current}
              onChange={(e) => setCurrent(e.target.value)}
              required
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="new_password">New Password</Label>
            <Input
              id="new_password"
              type="password"
              value={next}
              onChange={(e) => setNext(e.target.value)}
              placeholder="At least 8 characters"
              required
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="confirm_password">Confirm New Password</Label>
            <Input
              id="confirm_password"
              type="password"
              value={confirm}
              onChange={(e) => setConfirm(e.target.value)}
              required
            />
          </div>
          <Button type="submit" disabled={saving}>
            {saving && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
            Update Password
          </Button>
        </form>
      </CardContent>
    </Card>
  );
}

// ---------------------------------------------------------------------------
// Loading skeleton
// ---------------------------------------------------------------------------
function ProfileSkeleton() {
  return (
    <div className="min-h-screen bg-background">
      <MarketplaceNav />
      <div className="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
        <Skeleton className="h-8 w-48" />
        <div className="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-3">
          <div className="space-y-6 lg:col-span-1">
            <Card>
              <CardContent className="flex flex-col items-center p-6">
                <Skeleton className="h-24 w-24 rounded-full" />
                <Skeleton className="mt-4 h-5 w-32" />
                <Skeleton className="mt-2 h-4 w-40" />
                <div className="mt-6 w-full space-y-3">
                  {Array.from({ length: 4 }).map((_, i) => (
                    <Skeleton key={i} className="h-4 w-full" />
                  ))}
                </div>
              </CardContent>
            </Card>
            <div className="grid grid-cols-2 gap-3">
              {Array.from({ length: 4 }).map((_, i) => (
                <Skeleton key={i} className="h-20 w-full rounded-xl" />
              ))}
            </div>
          </div>
          <div className="lg:col-span-2">
            <Skeleton className="h-[420px] w-full rounded-xl" />
          </div>
        </div>
      </div>
    </div>
  );
}
