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
import { ActivityIndicator, View } from 'react-native';

const Stack = createNativeStackNavigator();
const Tab = createBottomTabNavigator();

function Tabs() {
  return (
    <Tab.Navigator screenOptions={{ headerShown:false, tabBarActiveTintColor:'#0b3d0b', tabBarStyle:{ backgroundColor:'#fff' } }}>
      <Tab.Screen name="Dashboard" component={DashboardScreen} options={{ tabBarLabel:'Dashboard' }} />
      <Tab.Screen name="Tickets" component={TicketsScreen} options={{ tabBarLabel:'My Tickets' }} />
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
            <Stack.Screen name="CreateTicket" component={CreateTicketScreen} options={{ title:'New Ticket' }} />
            <Stack.Screen name="TicketDetail" component={TicketDetailScreen} options={{ title:'Ticket Detail' }} />
          </>
        )}
      </Stack.Navigator>
    </NavigationContainer>
  );
}
