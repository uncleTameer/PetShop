-- PetShop Enhanced Database Schema
-- This file documents the MongoDB collections and their structure

-- Users Collection
-- Enhanced with security, loyalty, and address management
/*
users: {
  _id: ObjectId,
  fullName: String,
  email: String (unique),
  password: String (hashed),
  role: String (user/admin),
  profilePicture: String (file path),
  addresses: [{
    label: String,
    name: String,
    phone: String,
    line1: String,
    line2: String,
    city: String,
    zip: String,
    country: String,
    isDefault: Boolean
  }],
  preferences: {
    defaultCategoryId: ObjectId,
    emailNotifications: Boolean,
    lowStockThreshold: Number (default: 5)
  },
  loyaltyPoints: Number (default: 0),
  security: {
    twoFactorEnabled: Boolean (default: false),
    twoFactorSecret: String,
    lastPasswordChange: Date
  },
  lockout: {
    loginAttempts: Number,
    locked: Boolean,
    lockoutTime: Date
  },
  audit: {
    createdAt: Date,
    createdIp: String,
    createdUA: String,
    lastLoginAt: Date,
    lastLoginIp: String,
    lastLoginUA: String
  }
}
*/

-- Products Collection
-- Enhanced with stock tracking and alerts
/*
products: {
  _id: ObjectId,
  name: String,
  description: String,
  price: Number,
  originalPrice: Number,
  image: String,
  category: String,
  stock: Number,
  lowStockThreshold: Number (default: 5),
  status: String (active/inactive/out-of-stock),
  createdAt: Date,
  updatedAt: Date
}
*/

-- Reviews Collection
-- Enhanced with photos and verification
/*
reviews: {
  _id: ObjectId,
  userId: ObjectId,
  productId: ObjectId,
  rating: Number (1-5),
  text: String,
  photos: [String], // Array of file paths
  verifiedPurchase: Boolean,
  helpful: Number (upvotes),
  createdAt: Date,
  updatedAt: Date
}
*/

-- Wishlist Collection
/*
wishlist: {
  _id: ObjectId,
  userId: ObjectId,
  productId: ObjectId,
  addedAt: Date,
  priceWhenAdded: Number
}
*/

-- Alerts Collection
-- For back-in-stock and price-drop notifications
/*
alerts: {
  _id: ObjectId,
  userId: ObjectId,
  productId: ObjectId,
  type: String (back-in-stock/price-drop),
  targetPrice: Number (for price-drop alerts),
  isActive: Boolean,
  createdAt: Date,
  triggeredAt: Date
}
*/

-- Orders Collection
-- Enhanced with loyalty points and addresses
/*
orders: {
  _id: ObjectId,
  userId: ObjectId,
  items: [{
    productId: ObjectId,
    name: String,
    price: Number,
    quantity: Number
  }],
  total: Number,
  loyaltyPointsEarned: Number,
  loyaltyPointsUsed: Number,
  finalTotal: Number,
  shippingAddress: {
    label: String,
    name: String,
    phone: String,
    line1: String,
    line2: String,
    city: String,
    zip: String,
    country: String
  },
  status: String,
  createdAt: Date,
  updatedAt: Date
}
*/

-- Q&A Collection
/*
qa: {
  _id: ObjectId,
  productId: ObjectId,
  question: String,
  answer: String,
  askedBy: ObjectId,
  answeredBy: ObjectId,
  isAnswered: Boolean,
  helpful: Number (upvotes),
  createdAt: Date,
  answeredAt: Date
}
*/

-- Sessions Collection
-- For device management and security
/*
sessions: {
  _id: ObjectId,
  userId: ObjectId,
  sessionId: String,
  ip: String,
  userAgent: String,
  lastSeen: Date,
  isActive: Boolean,
  createdAt: Date
}
*/

-- Notifications Collection
-- For admin notifications and system alerts
/*
notifications: {
  _id: ObjectId,
  type: String,
  userId: ObjectId,
  title: String,
  message: String,
  isRead: Boolean,
  createdAt: Date,
  readAt: Date
}
*/

-- Categories Collection
/*
categories: {
  _id: ObjectId,
  name: String,
  description: String,
  image: String,
  isActive: Boolean,
  createdAt: Date
}
*/

-- Cart Collection
-- Enhanced with guest cart expiration
/*
cart: {
  _id: ObjectId,
  userId: ObjectId (null for guests),
  sessionId: String (for guests),
  items: [{
    productId: ObjectId,
    name: String,
    price: Number,
    quantity: Number,
    addedAt: Date
  }],
  expiresAt: Date (for guest carts),
  createdAt: Date,
  updatedAt: Date
}
*/
