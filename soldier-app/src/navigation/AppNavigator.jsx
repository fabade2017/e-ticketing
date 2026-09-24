import React from 'react';
import { NavigationContainer } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { useAuth } from '../context/AuthContext';
import LoginScreen from '../screens/LoginScreen';
import DashboardScreen from '../screens/DashboardScreen';
import TicketsScreen from '../screens/TicketsScreen';
import CreateTicketScreen from '../screens/CreateTicketScreen';
import TicketDetailScreen from '../screens/TicketDetailScreen';
import { ActivityIndicator, View, Text, Image } from 'react-native';

const Stack = createNativeStackNavigator();
const Tab = createBottomTabNavigator();

function HeaderTitle({ title }) {
  return (
    <View style={{ flexDirection:'row', alignItems:'center', gap:8 }}>
      <Image source={require('../../assets/icon.png')} style={{ width:28, height:28, borderRadius:6, backgroundColor:'#fff' }} resizeMode="contain" />
      <View>
        <Text style={{ color:'#fff', fontWeight:'800', fontSize:14, lineHeight:16 }}>NA E-Ticket</Text>
        <Text style={{ color:'#cbd5e1', fontSize:9, lineHeight:10 }}>{title || 'Nigerian Army • Secure • Offline-Capable'}</Text>
      </View>
    </View>
  );
}

function Tabs() {
  return (
    <Tab.Navigator screenOptions={{ headerShown:true, headerStyle:{ backgroundColor:'#0b3d0b' }, headerTintColor:'#fff', headerTitle: () => <HeaderTitle title="Soldier Portal" />, tabBarActiveTintColor:'#0b3d0b', tabBarStyle:{ backgroundColor:'#fff' } }}>
      <Tab.Screen name="Dashboard" component={DashboardScreen} options={{ tabBarLabel:'Dashboard', headerTitle: () => <HeaderTitle title="Dashboard" /> }} />
      <Tab.Screen name="Tickets" component={TicketsScreen} options={{ tabBarLabel:'My Tickets', headerTitle: () => <HeaderTitle title="My Tickets" /> }} />
    </Tab.Navigator>
  );
}

export default function AppNavigator() {
  const { user, loading } = useAuth();
  if (loading) return <View style={{flex:1, justifyContent:'center', alignItems:'center'}}><ActivityIndicator size="large" color="#0b3d0b" /></View>;

  return (
    <NavigationContainer>
      <Stack.Navigator screenOptions={{ headerStyle:{ backgroundColor:'#0b3d0b' }, headerTintColor:'#fff', headerTitleStyle:{ fontWeight:'800' } }}>
        {!user ? (
          <Stack.Screen name="Login" component={LoginScreen} options={{ headerShown:false }} />
        ) : (
          <>
            <Stack.Screen name="Main" component={Tabs} options={{ headerShown:false }} />
            <Stack.Screen name="CreateTicket" component={CreateTicketScreen} options={{ headerTitle: () => <HeaderTitle title="New Ticket" /> }} />
            <Stack.Screen name="TicketDetail" component={TicketDetailScreen} options={{ headerTitle: () => <HeaderTitle title="Ticket Detail" /> }} />
          </>
        )}
      </Stack.Navigator>
    </NavigationContainer>
  );
}
